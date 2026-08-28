<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Perseu\Auditoria\Traits\LogsBusinessActivity;

class TipoObra extends Model
{
    use LogsBusinessActivity;

    protected $table = 'tipos_obra';

    protected $fillable = [
        'codigo',
        'descricao',
    ];

    public function obras(): HasMany
    {
        return $this->hasMany(Obra::class);
    }
}
