<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Permiso extends Model
{
    use SoftDeletes;

    protected $table = 'permisos';

    protected $fillable = [
        'uuid',
        'user_id',
        'fecha',
        'tipo',
        'hora_inicio',
        'hora_fin',
        'es_remunerado',
        'motivo',
        'status',
        'autorizado_por',
        'fecha_gestion',
        'observacion_gestion',
    ];

    protected $casts = [
        'fecha'          => 'date',
        'es_remunerado'  => 'boolean',
        'fecha_gestion'  => 'datetime',
        'uuid'           => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    /** Minutos que cubre el permiso (hora_fin - hora_inicio). */
    public function getMinutosAttribute(): int
    {
        return (int) \Carbon\Carbon::parse($this->hora_inicio)
            ->diffInMinutes(\Carbon\Carbon::parse($this->hora_fin));
    }

    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }
}
