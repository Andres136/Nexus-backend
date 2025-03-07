<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SeguimientoCliente extends Model
{
    //
    protected $table = 'seguimiento_clientes';
    protected $fillable = [
        'cliente_id',
        'user_id',
        'tipo_contacto',
        'estado',
        'comentario',
    ];
    //Relación con la tabla clientes
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id'); 
    }
    //Relación con la tabla users
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
