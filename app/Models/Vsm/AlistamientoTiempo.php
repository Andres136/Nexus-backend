<?php

namespace App\Models\Vsm;

use Illuminate\Database\Eloquent\Model;

class AlistamientoTiempo extends Model
{
    protected $table = 'alistamiento_tiempos';
  
    protected $fillable = [
        'alistamiento_id',
        'user_id',
        'tipo',
        'fecha_hora',
        'razon',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime'
    ];

    public function alistamiento()
    {
        return $this->belongsTo(Alistamiento::class);
    }
}
