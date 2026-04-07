<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GestionCarteraHistorial extends Model
{
    protected $table = 'gestion_cartera_historial';

    protected $fillable = [
        'gestion_cartera_id',
        'user_id',
        'soporte',
        'observacion',
        'tipo',
        'fecha_compromiso'
    ];

    public function gestionCartera()
    {
        return $this->belongsTo(GestionCartera::class, 'gestion_cartera_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function soportes()
    {
        return $this->hasMany(GestionCarteraSoporte::class, 'historial_id');
    }

}
