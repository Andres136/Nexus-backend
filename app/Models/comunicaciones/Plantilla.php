<?php

namespace App\Models\comunicaciones;

use Illuminate\Database\Eloquent\Model;

class Plantilla extends Model
{
    protected $table = 'plantilla';

    protected $fillable = [
        'nombre',
        'tipo',
        'contenido_html',
        'video_url',
        'imagenes',
        'logos_empresas',
        'certificaciones',
        'redes_sociales',
        'descargas',
        'publicada',
        'imagen_principal',
    ];

    protected $casts = [
        'imagenes' => 'array',
        'logos_empresas' => 'array',
        'certificaciones' => 'array',
        'redes_sociales' => 'array',
        'descargas' => 'array',
        'publicada' => 'boolean',
    ];
}
