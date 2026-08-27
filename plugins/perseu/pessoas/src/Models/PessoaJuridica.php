<?php

namespace Perseu\Pessoas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Perseu\Pessoas\Enums\IndicadorContribuinteIcms;
use Perseu\Pessoas\Enums\RegimeTributario;

class PessoaJuridica extends Model
{
    use SoftDeletes;

    protected $table = 'pessoas_juridicas';

    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'inscricao_estadual',
        'indicador_contribuinte_icms',
        'cnae',
        'cnae_descricao',
        'regime_tributario',
        'data_abertura',
        'porte',
        'descricao_porte',
        'situacao_cadastral',
        'descricao_situacao_cadastral',
        'email',
        'telefone',
        'observacoes',
    ];

    protected $casts = [
        'regime_tributario'            => RegimeTributario::class,
        'indicador_contribuinte_icms'  => IndicadorContribuinteIcms::class,
        'data_abertura'                => 'date',
    ];

    /**
     * Endereço/Contato não usam SoftDeletes (ver CLAUDE.md) — sem isso,
     * excluir (mesmo soft-delete) uma Pessoa Jurídica deixava os dois
     * "vivos" mas órfãos, apontando pra um registro que só existia como
     * lixeira. Se esse registro fosse restaurado depois (Lixeira futura,
     * ou manualmente), o Endereço/Contato antigo reaparecia do nada —
     * bug relatado como "excluir e recriar com o mesmo CNPJ traz o
     * endereço antigo de volta". Roda em `deleting` (dispara tanto em
     * soft-delete quanto em forceDelete, já que SoftDeletes::delete()
     * ainda passa pelo fluxo normal de eventos do Model) — mesmo um
     * "excluir" que só move para a lixeira já limpa os dados
     * relacionados, que não têm lixeira própria.
     *
     * Contato é hasMany direto (pessoa_juridica_id) — sempre pertence a
     * uma única Pessoa Jurídica, então `->delete()` em massa na relação é
     * seguro. Endereço é BelongsToMany (pode, em tese, ser compartilhado
     * — o schema permite, embora nada no sistema hoje crie isso na
     * prática) — por segurança, só apaga o registro de fato se, depois
     * de desvincular esta Pessoa Jurídica, nenhuma outra Pessoa
     * Física/Jurídica ainda referenciar o mesmo Endereço.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (self $pessoaJuridica): void {
            $pessoaJuridica->contatos()->delete();

            $enderecoIds = $pessoaJuridica->enderecos()->pluck('enderecos.id');

            $pessoaJuridica->enderecos()->detach();

            foreach ($enderecoIds as $enderecoId) {
                $endereco = Endereco::find($enderecoId);

                if ($endereco && ! $endereco->pessoasFisicas()->exists() && ! $endereco->pessoasJuridicas()->exists()) {
                    $endereco->delete();
                }
            }
        });
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(CategoriaPessoa::class, 'pessoa_juridica_categoria');
    }

    public function setores(): BelongsToMany
    {
        return $this->belongsToMany(Setor::class, 'pessoa_juridica_setor');
    }

    public function enderecos(): BelongsToMany
    {
        return $this->belongsToMany(Endereco::class, 'pessoa_juridica_endereco')
            ->withPivot('tipo', 'principal')
            ->withTimestamps();
    }

    public function contatos(): HasMany
    {
        return $this->hasMany(Contato::class);
    }
}
