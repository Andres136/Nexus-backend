<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrdenComprasHistorial extends Model
{
    //
    protected $table = 'orden_compras_historial';   
    protected $fillable = [
        'orden_compra_id',
        'fecha_anterior',
        'fecha_nueva',
        'observacion',
        'usuario_id'
    ];

    public function ordenCompra()
    {
        return $this->belongsTo(Orden_Compra::class, 'orden_compra_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

}
