<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    
    protected $table = 'sedes';

    protected $fillable = [
        'nombre',
        'direccion',
   
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'sede_id');
    }  
    
    public function empresas()
    {
        return $this->belongsToMany(Empresa::class, 'empresa_sede', 'sede_id', 'empresa_id');
    }

    //relacion con inventarios
    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'sede_id');
    }
}
