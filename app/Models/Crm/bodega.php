<?php

namespace App\Models\Crm;

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
}
