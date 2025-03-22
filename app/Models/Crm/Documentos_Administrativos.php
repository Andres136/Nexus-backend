<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class Documentos_Administrativos extends Model
{
    protected $table = 'documentos_administrativos';
    protected $fillable = [
        'nombre',
        'archivo',
        'carpeta_id',
        'usuario_id',
    ];

    public function carpeta()
    {
        return $this->belongsTo(Carpeta::class, 'carpeta_id');
    }
}
