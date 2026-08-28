<?php

namespace Perseu\Pessoas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Perseu\Auditoria\Traits\LogsBusinessActivity;
use Perseu\Pessoas\Enums\IndicadorContribuinteIcms;
use Perseu\Pessoas\Enums\RegimeTributario;
use Perseu\Pessoas\Traits\CascadesRelatedDataOnForceDelete;

class PessoaJuridica extends Model
{
    use CascadesRelatedDataOnForceDelete;
    use LogsBusinessActivity;
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
