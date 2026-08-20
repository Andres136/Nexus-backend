<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoMaestro extends Model
{
    protected $table = 'documentos_maestros';

    protected $fillable = [
        'departamento_id',
        'user_id',
        'nombre',
        'tipo_documento',
        'codigo',
        'fecha_emision',
        'fecha_actualizacion',
        'version',
        'medio_fisico',
        'medio_digital',
        'retencion_gestion',
        'retencion_central',
        'disposicion_final',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_actualizacion' => 'date',
        'version' => 'integer',
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    }

    public function liderProceso()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
