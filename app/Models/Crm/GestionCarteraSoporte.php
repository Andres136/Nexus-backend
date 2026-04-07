<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class GestionCarteraSoporte extends Model
{
    protected $table = 'gestion_cartera_soportes';

    protected $fillable = [
        'historial_id',
        'archivo'
    ];

    public function historial()
    {
        return $this->belongsTo(GestionCarteraHistorial::class, 'historial_id');
    }
}
