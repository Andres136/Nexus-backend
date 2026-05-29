<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class Carpeta extends Model
{
    protected $table = 'carpetas';
    protected $fillable = ['nombre', 'parent_id'];

    public function documentos()
    {
        return $this->hasMany(Documentos_Administrativos::class, 'carpeta_id');
    }

    public function subcarpetas()
    {
        return $this->hasMany(Carpeta::class, 'parent_id');
    }

    public function padre()
    {
        return $this->belongsTo(Carpeta::class, 'parent_id');
    }
}
