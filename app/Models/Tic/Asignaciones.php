<?php

namespace App\Models\Tic;

use App\Models\Crm\empresa;
use App\Models\Crm\product;
use App\Models\Crm\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Asignaciones extends Model
{
    protected $table = 'asignaciones';

    protected $fillable = [
        'id',
        'id_usuario',
        'sede_id',
        'producto_id',
        'empresa_id',
        'fecha_asignacion',
        'fecha_devolucion',
        'usuario_asignacion_id',
        'observaciones',
        'activo',
    ];
    public $timestamps = true;
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function producto()
    {
        return $this->belongsTo(product::class, 'producto_id');
    }

    public function empresa()
    {
        return $this->belongsTo(empresa::class, 'empresa_id');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function usuarioRecibe()
    {
        return $this->belongsTo(User::class, 'usuario_asignacion_id');
    }

    
}
