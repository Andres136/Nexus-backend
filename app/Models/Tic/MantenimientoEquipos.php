<?php

namespace App\Models\Tic;

use App\Models\Crm\empresa;
use App\Models\Crm\product;
use App\Models\Crm\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MantenimientoEquipos extends Model
{
    protected $table = 'mantenimiento_equipos';

    protected $fillable = [
        'sede_id',
        'producto_id',
        'empresa_id',
        'usuario_id',
        'tipo',
        'fecha_programada',
        'fecha_ejecucion',
        'observaciones',
        'estado',
        'costo',
    ];

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function producto()
    {
        return $this->belongsTo(product::class);
    }

    public function empresa()
    {
        return $this->belongsTo(empresa::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function asignacion()
{
    return $this->hasOne(Asignaciones::class, 'producto_id', 'producto_id')
                ->where('activo', 1);
}
}
