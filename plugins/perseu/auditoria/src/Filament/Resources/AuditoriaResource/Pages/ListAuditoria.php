<?php

namespace Perseu\Auditoria\Filament\Resources\AuditoriaResource\Pages;

use Perseu\Auditoria\Filament\Resources\AuditoriaResource;
use Rmsramos\Activitylog\Resources\Activitylog\Pages\ListActivitylog;

class ListAuditoria extends ListActivitylog
{
    protected static string $resource = AuditoriaResource::class;
}
