<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MovimientoStock extends Model
{
    protected $table = 'movimientos_stock';

    protected $fillable = [
     'orden_trabajo_id',
     'orden_compra_id',
     'producto_id',
     'usuario_id',
     'tipo',
     'cantidad',
     'detalle',
     'razon',
     'pdf_path',
     'anulado '
    ];

    protected $casts = [
        'detalle' => 'array',
        'anulado' => 'boolean',
        'producto_id' => 'array',
    ];
    public function producto()
    {
        return $this->belongsTo(product::class, 'producto_id');
    }

    public function bodega()
    {
        return $this->belongsTo(bodega::class, 'bodega_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function ordenCompra()
    {
        return $this->belongsTo(Orden_Compra::class, 'orden_compra_id');
    }

  // Relación con Orden de Trabajo
    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenDeTrabajo::class, 'orden_trabajo_id');
    }
    //relacion con sede
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

}
