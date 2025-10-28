<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class ProductoEquivalentes extends Model
{
    protected $table = 'producto_equivalentes';
    protected $fillable = ['producto_id', 'equivalente_id', 'razon', 'cantidad', 'registrado_por'];

    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

}
