<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HorarioUsuarioBloque extends Model
{
    protected $table = 'horarios_usuario_bloques';

    protected $fillable = [
        'uuid',
        'user_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'orden',
        'status',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'orden' => 'integer',
        'status' => 'boolean',
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
}
