<?php

namespace App\Models\Hseq;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductoNoConformeArchivo extends Model
{
    protected $table = 'producto_no_conforme_archivos';

    protected $appends = ['url'];

    protected $fillable = [
        'producto_no_conforme_id',
        'archivo',
        'tipo',
        'descripcion',
    ];

    public function getUrlAttribute()
    {
        return Storage::disk('public')->url($this->archivo);
    }

    public function productoNoConforme()
    {
        return $this->belongsTo(ProductoNoConforme::class, 'producto_no_conforme_id');
    }
}
