<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Perseu\Auditoria\Traits\LogsBusinessActivity;
use Perseu\Comercial\Services\GeradorNumeroObra;
use Perseu\Pessoas\Models\Endereco;
use Perseu\Pessoas\Models\PessoaFisica;
use Perseu\Pessoas\Models\PessoaJuridica;

class Obra extends Model
{
    use LogsBusinessActivity;
    use SoftDeletes;

    protected $table = 'obras';

    // numero_obra e data_cadastro nunca são preenchidos pelo usuário —
    // gerados automaticamente no evento "creating" abaixo.
    protected $fillable = [
        'pessoa_fisica_id',
        'pessoa_juridica_id',
        'contato_pessoa_fisica_id',
        'tipo_obra_id',
        'endereco_id',
        'descricao',
        'revisao',
    ];

    protected $casts = [
        'data_cadastro' => 'datetime',
    ];

    public function pessoaFisica(): BelongsTo
    {
        return $this->belongsTo(PessoaFisica::class);
    }

    public function pessoaJuridica(): BelongsTo
    {
        return $this->belongsTo(PessoaJuridica::class);
    }

    public function contatoPessoaFisica(): BelongsTo
    {
        return $this->belongsTo(PessoaFisica::class, 'contato_pessoa_fisica_id');
    }

    public function tipoObra(): BelongsTo
    {
        return $this->belongsTo(TipoObra::class);
    }

    public function endereco(): BelongsTo
    {
        return $this->belongsTo(Endereco::class);
    }

    public function situacoes(): BelongsToMany
    {
        return $this->belongsToMany(SituacaoObra::class, 'obra_situacao');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Obra $obra): void {
            if (blank($obra->numero_obra)) {
                $tipoObra = TipoObra::findOrFail($obra->tipo_obra_id);
                $obra->numero_obra = GeradorNumeroObra::gerar(now()->year, $tipoObra);
            }

            if (blank($obra->data_cadastro)) {
                $obra->data_cadastro = now();
            }
        });
    }
}
