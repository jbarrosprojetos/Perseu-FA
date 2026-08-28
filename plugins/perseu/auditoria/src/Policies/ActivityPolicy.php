<?php

namespace Perseu\Auditoria\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Activitylog\Models\Activity;
use Webkul\Security\Models\User;

/**
 * Controla tanto a página de Auditoria (Configurações → Auditoria)
 * quanto a aba "Atividades" embutida em Pessoa Jurídica/Física/Projeto
 * (`Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager`) —
 * o RelationManager, por padrão do Filament
 * (`RelationManager::canViewForRecord()`), autoriza a aba chamando
 * `authorize('viewAny', Activity::class)`, que resolve pra cá
 * automaticamente. Ou seja: `viewAny` aqui É a permissão "separada da
 * permissão de ver/editar o próprio registro" pedida na tarefa — um
 * usuário pode editar uma Pessoa Jurídica sem enxergar sua aba de
 * Atividades, e vice-versa, dependendo só desta policy.
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
