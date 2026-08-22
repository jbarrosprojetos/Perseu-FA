<?php

namespace Perseu\Pessoas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CategoriaPessoa extends Model
{
    protected $table = 'categorias_pessoa';

    protected $fillable = [
        'descricao',
        'aplica_pf',
        'aplica_pj',
        'e_cliente',
        'e_fornecedor',
    ];

    protected $casts = [
        'aplica_pf'    => 'boolean',
        'aplica_pj'    => 'boolean',
        'e_cliente'    => 'boolean',
        'e_fornecedor' => 'boolean',
    ];

    public function pessoasFisicas(): BelongsToMany
    {
        return $this->belongsToMany(PessoaFisica::class, 'pessoa_fisica_categoria');
    }

    public function pessoasJuridicas(): BelongsToMany
    {
        return $this->belongsToMany(PessoaJuridica::class, 'pessoa_juridica_categoria');
    }
}
