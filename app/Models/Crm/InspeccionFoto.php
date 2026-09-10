<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class InspeccionFoto extends Model
{
    protected $table = 'inspeccion_fotos';

    protected $fillable = [
        'inspeccion_id',
        'ruta',
    ];

    public function inspeccion()
    {
        return $this->belongsTo(Inspeccion::class, 'inspeccion_id');
    }
}
