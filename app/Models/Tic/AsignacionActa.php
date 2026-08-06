<?php

namespace App\Models\Tic;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AsignacionActa extends Model
{
    protected $table = 'asignacion_actas';

    protected $fillable = [
        'asignacion_id',
        'tipo',
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

    public function asignacion()
    {
        return $this->belongsTo(Asignaciones::class, 'asignacion_id');
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
