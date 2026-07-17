<?php

namespace App\Models\Productividad;

use App\Models\Nomina\WorkSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class JornadaOperativa extends Model
{
    use SoftDeletes;

    protected $table = 'jornadas_operativas';

    const DISPONIBLE = 'DISPONIBLE';
    const EN_ACTIVIDAD = 'EN_ACTIVIDAD';
    const PAUSA = 'PAUSA';
    const FINALIZADA = 'FINALIZADA';

    protected $fillable = [
        'uuid',
        'user_id',
        'work_session_id',
        'fecha',
        'estado_actual',
        'iniciada_at',
        'finalizada_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'iniciada_at' => 'datetime',
        'finalizada_at' => 'datetime',
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

    public function workSession()
    {
        return $this->belongsTo(WorkSession::class, 'work_session_id');
    }

    public function actividades()
    {
        return $this->hasMany(ActividadOperativa::class, 'jornada_operativa_id');
    }

    public function eventos()
    {
        return $this->hasMany(EventoProductividad::class, 'jornada_operativa_id');
    }
}
