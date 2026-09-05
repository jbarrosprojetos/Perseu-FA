<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Perseu\Auditoria\Traits\LogsBusinessActivity;
use Webkul\Security\Models\User;

/**
 * Histórico de notas/anotações de um Projeto (ver CLAUDE.md, "Notas do
 * Projeto"). SEM `SoftDeletes`, mesma divergência já usada por
 * `ItemProjeto` na convenção padrão de Model de negócio — aqui não é
 * por causa de renumeração (que não existe, ver `numero_nota` abaixo),
 * é porque uma nota de USUÁRIO excluída dentro do prazo de 24h não
 * precisa de Lixeira própria, e uma nota de SISTEMA nunca é excluída
 * pela UI (`tipo_sistema` já cobre essa garantia sozinho).
 */
class NotaProjeto extends Model
{
    use LogsBusinessActivity;

    protected $table = 'notas_projeto';

    // numero_nota nunca é preenchido pelo usuário — gerado
    // automaticamente no evento "creating" abaixo (mesmo critério de
    // ItemProjeto::numero_item), por isso fora do $fillable.
    protected $fillable = [
        'projeto_id',
        'usuario_id',
        'texto',
        'tipo_sistema',
    ];

    protected $casts = [
        'tipo_sistema' => 'boolean',
    ];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * `numero_nota` = maior número já usado NAQUELE Projeto + 1,
     * começando em 1. Diferente de `ItemProjeto::numero_item`, este
     * número NUNCA é renumerado ao excluir uma nota (ver
     * `ProjetoResource::excluirNotaProjeto()`) — é só um identificador
     * sequencial de cada nota ao longo do tempo (não uma lista visível
     * ao cliente), então pode ficar com buraco na sequência depois de
     * uma exclusão, de propósito, pra preservar a identidade de cada
     * nota já existente.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (NotaProjeto $nota): void {
            if (blank($nota->numero_nota)) {
                $ultimoNumero = (int) static::where('projeto_id', $nota->projeto_id)->max('numero_nota');

                $nota->numero_nota = $ultimoNumero + 1;
            }
        });
    }

    /**
     * Regra de prazo de 24h (ver CLAUDE.md): uma nota de USUÁRIO só
     * pode ser editada/excluída pela tela dentro de 24h a partir de
     * `created_at`; uma nota de SISTEMA (`tipo_sistema = true`) NUNCA
     * pode, independente do prazo. `podeEditar()`/`podeExcluir()` hoje
     * compartilham exatamente a mesma regra — mantidos como dois
     * métodos (em vez de um só) porque a tarefa que os criou já previu
     * que edição e exclusão possam divergir no futuro, mesmo sem essa
     * necessidade concreta ainda.
     *
     * Usado tanto para a exibição condicional dos ícones de
     * editar/excluir (`ProjetoResource::linhaExibicaoNota()`) quanto
     * como validação de segurança no backend (`editarNotaProjeto()`/
     * `excluirNotaProjeto()`) — nunca confiar só em esconder o botão na
     * tela.
     */
    public function podeEditar(): bool
    {
        return $this->dentroDoPrazoDeEdicao();
    }

    public function podeExcluir(): bool
    {
        return $this->dentroDoPrazoDeEdicao();
    }

    protected function dentroDoPrazoDeEdicao(): bool
    {
        if ($this->tipo_sistema) {
            return false;
        }

        return $this->created_at !== null && $this->created_at->addHours(24)->isFuture();
    }
}
