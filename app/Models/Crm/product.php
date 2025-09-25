<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class product extends Model
{
    protected $table = 'products';
    protected $fillable = 
    ['siigo_id', 'name', 'description', 'code', 'categoria_id'];


    //Relación con la categoría
    public function categoria()
    {
        return $this->belongsTo(categoria::class, 'categoria_id');
    }

    // Relación con los productos equivalentes
    public function equivalentes()
    {
        return $this->belongsToMany(
            Product::class,
            'producto_equivalentes',
            'producto_id',
            'equivalente_id'
        )->withPivot('razon')->withTimestamps();    
    }

    //relacion con inventarios
    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'producto_id');
    }



}
