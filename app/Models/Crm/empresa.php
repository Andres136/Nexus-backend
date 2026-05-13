<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'email',
        'nit',
        'logo',
    ];

public function contrataciones()
{
    return $this->hasMany(\App\Models\Nomina\Contratacion::class, 'empresa_id');
}

public function sedes()
{
    return $this->belongsToMany(Sede::class, 'empresa_sede', 'empresa_id', 'sede_id');
}

// Relación con inventarios
public function inventarios()
{
    return $this->hasMany(Inventario::class, 'empresa_id');
}

}