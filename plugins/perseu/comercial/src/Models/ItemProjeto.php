<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Perseu\Auditoria\Traits\LogsBusinessActivity;
use Perseu\Comercial\Enums\OrigemItemProjeto;

/**
 * SEM `SoftDeletes` (diferente da convenção padrão de Model de negócio,
 * ver CLAUDE.md da raiz) — decisão deliberada: excluir um item RENUMERA
 * os itens seguintes daquele Projeto pra fechar o buraco na sequência
 * (`numero_item`), o que exige que o número excluído fique DE VERDADE
 * livre. Uma linha soft-deleted continuaria ocupando o slot no índice
 * único `(projeto_id, numero_item)` da migration, bloqueando a
 * renumeração do item seguinte pra esse mesmo número — SoftDeletes e
 * "fechar buracos na numeração" são mutuamente incompatíveis aqui. Item
 * de Projeto também é, como a própria tarefa que introduziu a exclusão
 * descreveu, "um detalhe operacional, não um cadastro central auditado
 * como Obra/Pessoa" — sem o mesmo valor de manter um rastro de "excluído
 * mas recuperável" que `Projeto`/`PessoaFisica`/`PessoaJuridica` têm.
 * `LogsBusinessActivity` continua funcionando normalmente sem
 * `SoftDeletes` (o próprio trait já trata esse caso — só pula o listener
 * de `forceDeleted`, que não existe sem o trait; o evento `deleted`
 * padrão do Spatie já cobre a exclusão de verdade). Ver
 * `ProjetoResource::excluirItemAvulso()` pra a exclusão + renumeração em
 * si.
 */
class ItemProjeto extends Model
{
    use LogsBusinessActivity;

    protected $table = 'itens_projeto';

    // numero_item nunca é preenchido pelo usuário — gerado
    // automaticamente no evento "creating" abaixo (mesmo critério já
    // usado por Projeto::numero_projeto), por isso fora do $fillable.
    protected $fillable = [
        'projeto_id',
        'origem',
        'produto_id',
        'descricao',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'porcentagem',
        'custo_unitario',
        'imposto_aplicado',
        'situacao_item_id',
    ];

    protected $casts = [
        'origem'           => OrigemItemProjeto::class,
        'quantidade'       => 'integer',
        'valor_unitario'   => 'decimal:2',
        'valor_total'      => 'decimal:2',
        'porcentagem'      => 'decimal:2',
        'custo_unitario'   => 'decimal:2',
        'imposto_aplicado' => 'decimal:2',
    ];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    /**
     * Notas de SISTEMA vinculadas a este item especificamente (base do
     * futuro ícone "Cálculos" no menu de cada item — ver CLAUDE.md,
     * "Notas do Projeto"). Geração automática ainda não implementada;
     * só a coluna/relação já existem.
     */
    public function notas(): HasMany
    {
        return $this->hasMany(NotaProjeto::class, 'item_projeto_id');
    }

    /**
     * `numero_item` = maior número já usado NAQUELE Projeto + 1,
     * formato `###` (`001`, `002`...), começando em `001`. Diferente de
     * `numero_projeto` (`Projeto`), números AQUI são reaproveitados —
     * excluir um item renumera os seguintes pra fechar o buraco (ver
     * `ProjetoResource::excluirItemAvulso()`), por isso não precisa
     * (nem pode, sem `SoftDeletes`) considerar registros excluídos
     * aqui: um `MAX()` simples nos registros ATUAIS já reflete a
     * sequência contígua depois de qualquer exclusão. O
     * `DB::transaction()` + `lockForUpdate()` na chamada
     * (`ProjetoResource::confirmarItemAvulso()`) cobre o caso de dois
     * cliques rápidos no mesmo Projeto; a primeira inserção de um
     * Projeto novo (sem nenhuma linha pra travar ainda) fica fora
     * dessa proteção — risco aceito, não é um fluxo multi-usuário
     * simultâneo no mesmo Projeto.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ItemProjeto $item): void {
            if (blank($item->numero_item)) {
                $ultimoNumero = (int) static::where('projeto_id', $item->projeto_id)->max('numero_item');

                $item->numero_item = str_pad((string) ($ultimoNumero + 1), 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
