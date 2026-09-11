<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Perseu\Auditoria\Traits\LogsBusinessActivity;

/**
 * Catálogo de Documentos (ver CLAUDE.md) — templates de Excel
 * (.xls/.xlsx/.xlsm) com marcadores de texto, usados pelo Perseu pra
 * gerar os documentos reais de um Projeto (Vistoria, Proposta, O.S.
 * etc.). `arquivo` é só o CAMINHO no disco `local` — o conteúdo do
 * arquivo nunca é guardado no banco.
 */
class Documento extends Model
{
    use LogsBusinessActivity;
    use SoftDeletes;

    protected $table = 'documentos';

    protected $fillable = [
        'descricao',
        'arquivo',
    ];
}
