<?php

namespace Perseu\Pessoas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Perseu\Auditoria\Traits\LogsBusinessActivity;

class Endereco extends Model
{
    use LogsBusinessActivity;

    protected $table = 'enderecos';

    protected $fillable = [
        'cep',
        'uf',
        'municipio',
        'bairro',
        'logradouro',
        'numero',
        'complemento',
    ];

    public function pessoasFisicas(): BelongsToMany
    {
        return $this->belongsToMany(PessoaFisica::class, 'pessoa_fisica_endereco')
            ->withPivot('tipo', 'principal')
            ->withTimestamps();
    }

    public function pessoasJuridicas(): BelongsToMany
    {
        return $this->belongsToMany(PessoaJuridica::class, 'pessoa_juridica_endereco')
            ->withPivot('tipo', 'principal')
            ->withTimestamps();
    }
}
