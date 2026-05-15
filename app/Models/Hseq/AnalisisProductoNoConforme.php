<?php

namespace App\Models\Hseq;

use App\Models\Estados;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AnalisisProductoNoConforme extends Model
{
    protected $table = 'analisis_productos_no_conformes';

    protected $fillable = [
        'producto_no_conforme_id',
        'analista_id',
        'fecha_analisis',
        'causa_raiz',
        'acciones_correctivas',
        'acciones_preventivas',
        'observaciones',
        'estado_id',
        'fecha_cierre',
        'archivo_evidencia',
        'responsable_cierre_id'
    ];

    public function productoNoConforme()
    {
        return $this->belongsTo(ProductoNoConforme::class, 'producto_no_conforme_id');
    }

    public function analista()
    {
        return $this->belongsTo(User::class, 'analista_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estados::class, 'estado_id');
    }

    public function responsableCierre()
    {
        return $this->belongsTo(User::class, 'responsable_cierre_id');
    }
}
