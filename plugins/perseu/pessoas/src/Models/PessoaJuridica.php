<?php

namespace Perseu\Pessoas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'cnae',
        'regime_tributario',
        'data_abertura',
        'email',
        'telefone',
        'observacoes',
    ];

    protected $casts = [
        'regime_tributario' => RegimeTributario::class,
        'data_abertura'     => 'date',
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
