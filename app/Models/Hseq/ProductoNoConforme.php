<?php

namespace App\Models\Hseq;

use App\Models\Crm\Cliente;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\product;
use App\Models\Estados;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProductoNoConforme extends Model
{
    protected $table = 'productos_no_conformes';

    protected $fillable = [
        'cliente_id',
        'comercial_id',
        'producto_id',
        'orden_compra_id',
        'fecha_reporte',
        'cantidad_afectada',
        'descripcion_inicial',
        'tipo_falla',
        'estado_id'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function comercial()
    {
        return $this->belongsTo(User::class, 'comercial_id');
    }

    public function producto()
    {
        return $this->belongsTo(product::class, 'producto_id');
    }

    public function ordenCompra()
    {
        return $this->belongsTo(Orden_Compra::class, 'orden_compra_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estados::class, 'estado_id');
    }

    public function analisis()
    {
        return $this->hasOne(AnalisisProductoNoConforme::class, 'producto_no_conforme_id');
    }
}
