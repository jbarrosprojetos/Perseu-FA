<?php

namespace Perseu\Auditoria\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\ActivityLogStatus;
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
 *
 * ## `forceDeleted` — logado manualmente, FORA do mecanismo padrão do Spatie (2026-08-29)
 *
 * Confirmado lendo o vendor (não assumido) que o Spatie Activitylog
 * NUNCA logou exclusão definitiva: `LogsActivity::eventsToBeRecorded()`
 * só inclui `created`/`updated`/`deleted` (+ `restored` quando o Model
 * usa `SoftDeletes`) — não existe nenhuma referência a `forceDeleted`
 * em `vendor/spatie/laravel-activitylog`. Confirmado também num banco
 * real: `Activity::distinct()->pluck('event')` só retornava
 * `created/updated/deleted/restored`, nunca `forceDeleted`, mesmo após
 * vários `forceDelete()` reais no sistema — não era falta de dado
 * limpo, era o evento nunca ter sido logado.
 *
 * `Illuminate\Database\Eloquent\SoftDeletes::forceDelete()` já dispara
 * os eventos Eloquent `forceDeleting`/`forceDeleted` nativamente (é
 * assim que `Perseu\Pessoas\Traits\CascadesRelatedDataOnForceDelete`
 * já se pendura em `forceDeleting` pra cascata de Endereço/Contato) —
 * só não existia NENHUM listener registrando isso como uma Activity.
 * `bootLogsBusinessActivity()` abaixo fecha essa lacuna com um
 * listener PRÓPRIO em `forceDeleted` (depois da exclusão de fato,
 * mesmo timing que o Spatie usa pro evento `deleted` — o registro já
 * não existe mais no banco nesse ponto, mas a instância em memória
 * ainda tem os atributos, então `performedOn($model)` funciona
 * normalmente), sem mexer em `eventsToBeRecorded()`/no fluxo interno
 * do Spatie (mais seguro que tentar encaixar um evento novo dentro da
 * maquinaria genérica do pacote, que assume um conjunto fixo de nomes
 * de evento em vários pontos internos).
 *
 * Só registra o listener em Models que REALMENTE usam `SoftDeletes`
 * (`forceDeleted()` é um método estático que só existe nesse trait —
 * chamá-lo num Model sem `SoftDeletes` explodiria com "Call to
 * undefined method"). Hoje isso é `Obra`/`PessoaFisica`/
 * `PessoaJuridica` — os 3 únicos Models de negócio com `SoftDeletes`
 * (ver `Perseu\Auditoria\Support\TrashCatalog`).
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

    protected static function bootLogsBusinessActivity(): void
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            return;
        }

        static::forceDeleted(function (Model $model): void {
            if (app(ActivityLogStatus::class)->disabled()) {
                return;
            }

            activity()
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->event('forceDeleted')
                ->log('forceDeleted');
        });
    }
}
