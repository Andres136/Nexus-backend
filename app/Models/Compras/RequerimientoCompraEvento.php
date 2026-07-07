<?php

namespace App\Models\Compras;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RequerimientoCompraEvento extends Model
{
    protected $table = 'requerimiento_compra_eventos';

    protected $fillable = [
        'requerimiento_compra_id',
        'user_id',
        'tipo_evento',
        'estado_anterior',
        'estado_nuevo',
        'comentario',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function requerimiento()
    {
        return $this->belongsTo(RequerimientoCompra::class, 'requerimiento_compra_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
