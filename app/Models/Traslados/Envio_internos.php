<?php

namespace App\Models\Traslados;

use App\Models\Crm\empresa;
use App\Models\Crm\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Envio_internos extends Model
{
    protected $table = 'envios_internos';

    protected $fillable = [
        'sede_origen_id',
        'sede_destino_id',
        'usuario_id',
        'estado_id',
        'fecha_envio',
        'notas',
        'empresa_id'
    ];

    protected $casts = [
        'fecha_envio' => 'date',
    ];

    // Relaciones
    public function detalles()
    {
        return $this->hasMany(Detalles_envio_internos::class, 'envio_interno_id');
    }

    //Relacion con sede origen
    public function sedeOrigen()
    {
        return $this->belongsTo(Sede::class, 'sede_origen_id');
    }
    //Relacion con sede destino
    public function sedeDestino()
    {
        return $this->belongsTo(Sede::class, 'sede_destino_id');
    }
    //Relacion con usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    //relacion  con empresa
    public function empresa()
    {
        return $this->belongsTo(empresa::class, 'empresa_id');
    }
   
    

}
