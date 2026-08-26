<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DescargoActa extends Model
{
    protected $table = 'descargo_actas';

    protected $fillable = [
        'descargo_id',
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

    public function descargo()
    {
        return $this->belongsTo(Descargo::class, 'descargo_id');
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
