<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapacitacionActaEnvio extends Model
{
    protected $table = 'capacitacion_acta_envios';

    protected $fillable = [
        'acta_id', 'user_id', 'empresa_id', 'empresa_nombre', 'usuario_apellidos',
        'numero_documento', 'enviado_por', 'token', 'estado', 'enviada_at',
        'firmada_at', 'firma_nombre', 'firma_imagen', 'firma_ip', 'firma_user_agent',
    ];

    protected $hidden = ['firma_ip', 'firma_user_agent'];

    protected $casts = [
        'enviada_at' => 'datetime',
        'firmada_at' => 'datetime',
    ];

    public function acta() { return $this->belongsTo(CapacitacionActa::class, 'acta_id'); }
    public function usuario() { return $this->belongsTo(User::class, 'user_id'); }
    public function empresa() { return $this->belongsTo(\App\Models\Crm\empresa::class, 'empresa_id'); }
}
