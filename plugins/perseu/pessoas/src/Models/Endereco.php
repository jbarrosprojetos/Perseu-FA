<?php

namespace Perseu\Pessoas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            ->withPivot('principal')
            ->withTimestamps();
    }

    public function pessoasJuridicas(): BelongsToMany
    {
        return $this->belongsToMany(PessoaJuridica::class, 'pessoa_juridica_endereco')
            ->withPivot('principal')
            ->withTimestamps();
    }

    /**
     * Tags de finalidade deste Endereço (Comercial, Obra, Cobrança,
     * etc.) — ver EnderecoTipo e "Tipo de Endereço como tag" no
     * CLAUDE.md deste plugin.
     */
    public function tipos(): HasMany
    {
        return $this->hasMany(EnderecoTipo::class);
    }
}
