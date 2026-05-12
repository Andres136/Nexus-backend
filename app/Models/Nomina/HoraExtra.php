<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HoraExtra extends Model
{
    use SoftDeletes;

    protected $table = 'horas_extras';

    protected $fillable = [
        'uuid',
        'user_id',
        'fecha',
        'horas',
        'tipo',
        'motivo',
        'status',
        'autorizado_por',
        'fecha_gestion',
        'observacion_gestion',
    ];

    protected $casts = [
        'fecha'         => 'date',
        'horas'         => 'decimal:2',
        'fecha_gestion' => 'datetime',
        'uuid'          => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
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
