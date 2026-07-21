<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class GestionCarteraPivote extends Model
{
    protected $table = 'gestion_cartera_pivote';

    protected $fillable = [
        'gestion_cartera_id',
        'valor_pago',
        'fecha_pago',
        'observacion'
    ];

    public function gestionCartera()
    {
        return $this->belongsTo(GestionCartera::class, 'gestion_cartera_id');
    }
}
