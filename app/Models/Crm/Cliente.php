<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $fillable = [
       'nombre',
       'email',
       'telefono',
       'direccion',
         'nit',
       'user_id',
    ];
    //Relación con la tabla seguimiento_clientes
    public function seguimientos()
    {
        return $this->hasMany(SeguimientoCliente::class, 'cliente_id');
    }

    //Relación con la tabla usuarios
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    // Relación con la tabla ordenes_compra
    public function ordenes()
    {
        return $this->hasMany(Orden_Compra::class, 'cliente_id');
    }
}
