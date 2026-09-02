<?php

namespace Perseu\Auditoria\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Activitylog\Models\Activity;
use Webkul\Security\Models\User;

/**
 * Controla a página central de Auditoria (Configurações → Auditoria).
 *
 * Até 2026-08-29 esta policy também controlava a aba "Atividades"
 * embutida em Pessoa Jurídica/Física/Projeto
 * (`Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager`,
 * que se autorizava chamando `authorize('viewAny', Activity::class)`
 * via `RelationManager::canViewForRecord()`) — essas abas foram
 * removidas (central única de auditoria, ver AuditoriaResource), então
 * hoje `viewAny`/`view` aqui só valem pra esta página.
 *
 * As chaves exatas (`view_any_auditoria_auditoria`/
 * `view_auditoria_auditoria`) vêm da convenção de geração de permissões
 * do Shield deste projeto (`Webkul\PluginManager\PermissionManager`) —
 * baseada no nome/namespace do Resource (`AuditoriaResource`, plugin
 * `auditoria`), não do Model (`Spatie\Activitylog\Models\Activity`).
 */
class ActivityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_auditoria_auditoria');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->can('view_auditoria_auditoria');
    }
}
