<?php

namespace App\Models\Vsm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AlistamientoUsuarioDetalle extends Model
{
    protected $table = 'alistamiento_usuario_detalles';

    protected $fillable = [
        'alistamiento_id',
        'usuario_id',
        'detalle_id',
        'cantidad_alistada',
    ];

    public function alistamiento()
    {
        return $this->belongsTo(Alistamiento::class, 'alistamiento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalle()
    {
        return $this->belongsTo(AlistamientoDetalle::class, 'detalle_id');
    }
}
