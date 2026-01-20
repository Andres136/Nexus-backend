<?php

namespace App\Models\Traslados;

use App\Models\Crm\empresa;
use App\Models\Crm\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ResponsabilidadUser extends Model
{
    protected $table = 'responsabilidades_user';

    protected $fillable = [
       'user_id',
       'responsabilidad_id',
       'bodega_id',
       'sede_id',
       'activo',
       'fecha_asignacion',
       'fecha_fin',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_asignacion' => 'datetime',
        'fecha_fin' => 'datetime',
    ];


    /**
     * Relación con el modelo de Usuario.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el modelo de Responsabilidad.
     */
    public function responsabilidad()
    {
        return $this->belongsTo(Responsabilidad::class);
    }

    public function empresa()
    {
        return $this->belongsTo(empresa::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }
}
