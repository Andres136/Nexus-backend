<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class CarteraGestionMensual extends Model
{
    protected $table = 'cartera_gestion_mensual';

    protected $fillable = [
        'anio',
        'mes',
        'cartera_vencidas',
        'cartera_gestionadas',
        'cartera_pct_gestion',
    ];
}
