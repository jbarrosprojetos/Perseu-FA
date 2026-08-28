<?php

namespace Perseu\Auditoria\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Ponto único de configuração de auditoria para os Models de cadastro de
 * negócio do Perseu (ver CLAUDE.md, "Convenção de auditoria") — em vez de
 * cada Model escrever seu próprio `getActivitylogOptions()` (repetindo a
 * mesma lógica), basta `use LogsBusinessActivity;` para herdar o padrão
 * do projeto:
 *
 * - `logFillable()` — audita os mesmos campos que já são
 *   mass-assignable (`$fillable`) no Model, sem precisar listar os
 *   atributos de novo aqui nem no Model que usa o trait; um campo
 *   adicionado ao `$fillable` já entra automaticamente na auditoria.
 * - `logOnlyDirty()` — só grava um log quando algo de fato mudou (evita
 *   registros de "update" vazios quando o formulário é salvo sem
 *   alterações).
 * - `dontSubmitEmptyLogs()` — reforça o ponto acima: se depois do dirty
 *   check não sobrar nenhum atributo alterado, não grava nada.
 *
 * `causer` (quem fez) e `event` (created/updated/deleted) são
 * automáticos do próprio Spatie — não precisam de configuração aqui.
 *
 * Um Model com necessidade específica (ex: esconder um campo sensível do
 * log) pode sobrescrever `getActivitylogOptions()` normalmente depois de
 * usar o trait — `use` + declarar o método de novo no Model vence, é o
 * comportamento padrão de resolução de trait do PHP.
 */
trait LogsBusinessActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
