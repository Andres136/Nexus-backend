<?php

namespace App\Models\Productividad;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EventoProductividad extends Model
{
    protected $table = 'eventos_productividad';

    const UPDATED_AT = null;

    protected $fillable = [
        'uuid',
        'user_id',
        'jornada_operativa_id',
        'actividad_operativa_id',
        'tipo_evento',
        'origen_type',
        'origen_id',
        'ocurrio_at',
        'resumen',
        'metricas',
    ];

    protected $casts = [
        'ocurrio_at' => 'datetime',
        'metricas' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jornadaOperativa()
    {
        return $this->belongsTo(JornadaOperativa::class, 'jornada_operativa_id');
    }

    public function actividadOperativa()
    {
        return $this->belongsTo(ActividadOperativa::class, 'actividad_operativa_id');
    }

    public function origen()
    {
        return $this->morphTo();
    }
}
