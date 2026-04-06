<?php

namespace App\Models\Vsm;

use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Crm\product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Alistamiento extends Model
{
    protected $table = 'alistamiento';
    protected $fillable = [
        'orden_trabajo_id',
       
        'usuario_id',
        'cantidad',
        'duracion_segundos',
        'estado',
        'fecha'
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

     // RELACIONES
    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenDeTrabajo::class);
    }

 

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function tiempos()
    {
        return $this->hasMany(AlistamientoTiempo::class);
    }


    public function detalles()
    {
        return $this->hasMany(AlistamientoDetalle::class);
    }

    // --------------------------------------------
    // MÉTODO: CALCULAR TIEMPO PRODUCTIVO
    // --------------------------------------------
    public function calcularDuracion()
    {
        $eventos = $this->tiempos()->orderBy('fecha_hora')->get();

        $totalSegundos = 0;
        $inicio = null;

        foreach ($eventos as $evento) {

            if ($evento->tipo === 'REANUDACION' || $evento->tipo === 'INICIO') {
                $inicio = $evento->fecha_hora;
            }

            if (($evento->tipo === 'PAUSA' || $evento->tipo === 'FINALIZACION') && $inicio) {
                $totalSegundos += $inicio->diffInSeconds($evento->fecha_hora);
                $inicio = null;
            }
        }

        $this->duracion_segundos = $totalSegundos;
        $this->save();

        return $totalSegundos;
}

public function usuarios()
{
    return $this->belongsToMany(User::class, 'alistamiento_usuario', 'alistamiento_id', 'usuario_id')
                ->using(AlistamientoUsuario::class)
                ->withPivot('estado', 'inicio', 'pausado_en', 'tiempo_segundos')
                ->withTimestamps();
}

   


}