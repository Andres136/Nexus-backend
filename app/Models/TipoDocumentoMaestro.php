<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDocumentoMaestro extends Model
{
    protected $table = 'tipos_documento_maestro';

    protected $fillable = ['nombre'];
}
