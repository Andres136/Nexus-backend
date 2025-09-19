<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class categoria extends Model
{
    protected $table = 'categorias';
    protected $fillable = 
    ['nombre', 'descripcion'];

    //Relación con los productos
    public function products()
    {
        return $this->hasMany(product::class, 'categoria_id');
    }

}
