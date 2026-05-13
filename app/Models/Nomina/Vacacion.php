<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vacacion extends Model
{
    use SoftDeletes;

    protected $table = 'vacaciones';

    protected $fillable = [
        'uuid',
        'user_id',
        'fecha_inicio',
        'fecha_fin',
        'dias_habiles',
        'tipo',
        'motivo',
        'status',
        'autorizado_por',
        'fecha_gestion',
        'observacion_gestion',
    ];

    protected $casts = [
        'fecha_inicio'   => 'date',
        'fecha_fin'      => 'date',
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

    public function getDiasCalendarioAttribute(): int
    {
        return (int) $this->fecha_inicio->diffInDays($this->fecha_fin) + 1;
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
