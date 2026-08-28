<?php

namespace Perseu\Auditoria\Filament\Resources\AuditoriaResource\Pages;

use Perseu\Auditoria\Filament\Resources\AuditoriaResource;
use Rmsramos\Activitylog\Resources\Activitylog\Pages\ViewActivitylog;

class ViewAuditoria extends ViewActivitylog
{
    public static function getResource(): string
    {
        return AuditoriaResource::class;
    }
}
