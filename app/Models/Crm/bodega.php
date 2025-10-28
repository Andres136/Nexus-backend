<?php

namespace App\Models\Crm;

use App\Models\Estados;
use Illuminate\Database\Eloquent\Model;

class bodega extends Model
{
    protected $table = 'bodegas';
    protected $fillable = 
    ['nombre', 'direccion', 'sede_id', 'estado_id'];



    //relacion con inventarios  

    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'bodega_id');
    }
    //relacion con sedes
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    //relacion con estados
    public function estado()
    {
        return $this->belongsTo(Estados::class, 'estado_id');
    }

    public function detallesEnvioInternos()
    {
        return $this->hasMany(\App\Models\Traslados\Detalles_envio_internos::class, 'bodega_origen_id');
    }
}
