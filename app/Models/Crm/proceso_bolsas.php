<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class proceso_bolsas extends Model
{
    protected $table = 'proceso_bolsas';

    protected $fillable = [
        'nombre',
    ];

   // Relación con el modelo OrdenCompraProveedor
   public function detalles()
   {
       return $this->belongsTo(OrdenCompraProveedorDetalle::class, 'proceso_bolsas_id', 'id');
   }
   
}
