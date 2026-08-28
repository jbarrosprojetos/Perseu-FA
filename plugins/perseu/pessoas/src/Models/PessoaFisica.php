<?php

namespace Perseu\Pessoas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Perseu\Auditoria\Traits\LogsBusinessActivity;
use Perseu\Pessoas\Enums\EstadoCivil;
use Perseu\Pessoas\Enums\Sexo;
use Perseu\Pessoas\Traits\CascadesRelatedDataOnForceDelete;

class PessoaFisica extends Model
{
    use CascadesRelatedDataOnForceDelete;
    use LogsBusinessActivity;
    use SoftDeletes;

    protected $table = 'pessoas_fisicas';

    protected $fillable = [
        'nome',
        'telefone',
        'telefone_whatsapp',
        'email',
        'data_nascimento',
        'rg',
        'cpf',
        'estado_civil',
        'sexo',
        'profissao',
        'observacoes',
    ];

    protected $casts = [
        'telefone_whatsapp' => 'boolean',
        'data_nascimento'   => 'date',
        'estado_civil'      => EstadoCivil::class,
        'sexo'              => Sexo::class,
    ];

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(CategoriaPessoa::class, 'pessoa_fisica_categoria');
    }

    public function enderecos(): BelongsToMany
    {
        return $this->belongsToMany(Endereco::class, 'pessoa_fisica_endereco')
            ->withPivot('tipo', 'principal')
            ->withTimestamps();
    }

    public function contatos(): HasMany
    {
        return $this->hasMany(Contato::class);
    }
}
