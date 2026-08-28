<?php

use Perseu\Auditoria\Filament\Resources\AuditoriaResource;

/**
 * Só view_any/view — log de atividade é gerado pelo sistema, nunca
 * criado/editado/excluído manualmente pelo usuário pela UI (não faz
 * sentido gerar create/update/delete para este Resource).
 */
return [
    'resources' => [
        'manage' => [
            AuditoriaResource::class => ['view_any', 'view'],
        ],
    ],
];
