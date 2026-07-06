<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RecuperacionTiempo extends Model
{
    use SoftDeletes;

    protected $table = 'recuperaciones_tiempo';

    protected $fillable = [
        'uuid',
        'user_id',
        'work_session_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'minutos_autorizados',
        'minutos_usados',
        'motivo',
        'status',
        'autorizado_por',
        'fecha_gestion',
        'observacion_gestion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'minutos_autorizados' => 'integer',
        'minutos_usados' => 'integer',
        'fecha_gestion' => 'datetime',
        'uuid' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sesion()
    {
        return $this->belongsTo(WorkSession::class, 'work_session_id');
    }

    public function autorizador()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }
}
