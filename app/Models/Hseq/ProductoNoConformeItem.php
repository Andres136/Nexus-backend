<?php

namespace App\Models\Hseq;

use App\Models\Crm\product;
use Illuminate\Database\Eloquent\Model;

class ProductoNoConformeItem extends Model
{
    protected $table = 'producto_no_conforme_items';

    protected $fillable = [
        'producto_no_conforme_id',
        'producto_id',
        'cantidad_afectada',
    ];

    public function productoNoConforme()
    {
        return $this->belongsTo(ProductoNoConforme::class, 'producto_no_conforme_id');
    }

    public function producto()
    {
        return $this->belongsTo(product::class, 'producto_id');
    }
}
