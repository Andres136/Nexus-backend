<?php

namespace App\Models\Tic;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MantenimientoActa extends Model
{
    protected $table = 'mantenimiento_actas';

    protected $fillable = [
        'mantenimiento_id',
        'user_id',
        'usuario_nombre',
        'observaciones',
        'generada_por',
        'token',
        'estado',
        'generada_at',
        'firmada_at',
        'firma_nombre',
        'firma_imagen',
        'firma_ip',
        'firma_user_agent',
    ];

    protected $hidden = ['firma_ip', 'firma_user_agent'];

    protected $casts = [
        'generada_at' => 'datetime',
        'firmada_at' => 'datetime',
    ];

    public function mantenimiento()
    {
        return $this->belongsTo(MantenimientoEquipos::class, 'mantenimiento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function generadaPor()
    {
        return $this->belongsTo(User::class, 'generada_por');
    }
}
