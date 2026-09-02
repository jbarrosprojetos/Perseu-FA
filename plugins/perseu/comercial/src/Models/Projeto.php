<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Perseu\Auditoria\Traits\LogsBusinessActivity;
use Perseu\Comercial\Services\GeradorNumeroProjeto;
use Perseu\Pessoas\Models\Endereco;
use Perseu\Pessoas\Models\PessoaFisica;
use Perseu\Pessoas\Models\PessoaJuridica;

class Projeto extends Model
{
    use LogsBusinessActivity;
    use SoftDeletes;

    protected $table = 'projetos';

    // numero_projeto e data_cadastro nunca são preenchidos pelo usuário —
    // gerados automaticamente no evento "creating" abaixo. `revisao`
    // (trazida de volta em 2026-09-02, ver CLAUDE.md) também fica de
    // fora: nunca teve um input editável, só o `default(0)` da
    // migration — mesmo critério, sem incluir no `$fillable`.
    protected $fillable = [
        'pessoa_fisica_id',
        'pessoa_juridica_id',
        'contato_pessoa_fisica_id',
        'tipo_projeto_id',
        'endereco_id',
        'descricao',
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

    public function tipoProjeto(): BelongsTo
    {
        return $this->belongsTo(TipoProjeto::class);
    }

    public function endereco(): BelongsTo
    {
        return $this->belongsTo(Endereco::class);
    }

    public function situacoes(): BelongsToMany
    {
        return $this->belongsToMany(SituacaoProjeto::class, 'projeto_situacao');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Projeto $projeto): void {
            if (blank($projeto->numero_projeto)) {
                $tipoProjeto = TipoProjeto::findOrFail($projeto->tipo_projeto_id);
                $projeto->numero_projeto = GeradorNumeroProjeto::gerar(now()->year, $tipoProjeto);
            }

            if (blank($projeto->data_cadastro)) {
                $projeto->data_cadastro = now();
            }
        });
    }
}
