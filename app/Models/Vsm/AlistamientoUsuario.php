<?php

namespace App\Models\Vsm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AlistamientoUsuario extends Pivot
{
    protected $table = 'alistamiento_usuario';
    protected $fillable = [
        'alistamiento_id',
        'usuario_id',
        'jornada_laboral_id',
        'horas_semanales_snapshot',
        'estado',
        'inicio',
        'pausado_en',
        'tiempo_segundos',
        'razon',
    ];
    public $timestamps = true;

    protected $casts = [
        'horas_semanales_snapshot' => 'float',
    ];

    public function alistamiento()
    {
        return $this->belongsTo(Alistamiento::class, 'alistamiento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
