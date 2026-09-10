<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CarteraGestionMensualUsuario extends Model
{
    protected $table = 'cartera_gestion_mensual_usuario';

    protected $fillable = [
        'user_id',
        'anio',
        'mes',
        'cartera_vencidas',
        'cartera_gestionadas',
        'cartera_pct_gestion',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
