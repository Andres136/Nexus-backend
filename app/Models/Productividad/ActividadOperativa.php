<?php

namespace App\Models\Productividad;

use App\Models\Tareas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ActividadOperativa extends Model
{
    use SoftDeletes;

    protected $table = 'actividades_operativas';

    const TIPO_TAREA = 'TAREA';
    const TIPO_DISPONIBLE = 'DISPONIBLE';
    const TIPO_OTRA_ACTIVIDAD = 'OTRA_ACTIVIDAD';
    const TIPO_AUTOMATICA = 'AUTOMATICA';

    const ESTADO_ACTIVA = 'ACTIVA';
    const ESTADO_PAUSADA = 'PAUSADA';
    const ESTADO_COMPLETADA = 'COMPLETADA';
    const ESTADO_CANCELADA = 'CANCELADA';
    const ESTADO_BLOQUEADA = 'BLOQUEADA';
    const ESTADO_INTERRUMPIDA = 'INTERRUMPIDA';

    const ESTADOS_ABIERTOS = [self::ESTADO_ACTIVA, self::ESTADO_PAUSADA];

    protected $fillable = [
        'uuid',
        'jornada_operativa_id',
        'user_id',
        'tipo',
        'tarea_id',
        'categoria_id',
        'titulo',
        'descripcion',
        'estado',
        'inicio_at',
        'fin_at',
        'segundos',
        'resultado',
        'motivo_bloqueo',
        'creada_por',
        'cerrada_por',
    ];

    protected $casts = [
        'inicio_at' => 'datetime',
        'fin_at' => 'datetime',
        'segundos' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });
    }

    public function jornadaOperativa()
    {
        return $this->belongsTo(JornadaOperativa::class, 'jornada_operativa_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tarea()
    {
        return $this->belongsTo(Tareas::class, 'tarea_id');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaActividad::class, 'categoria_id');
    }

    public function creadaPor()
    {
        return $this->belongsTo(User::class, 'creada_por');
    }

    public function cerradaPor()
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }

    public function eventos()
    {
        return $this->hasMany(EventoProductividad::class, 'actividad_operativa_id');
    }

    public function correcciones()
    {
        return $this->hasMany(CorreccionActividadOperativa::class, 'actividad_operativa_id');
    }
}
