<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class Carpeta extends Model
{
    protected $table = 'carpetas';
    protected $fillable = ['nombre'];   
    

    //relacion con documentos administrativos
    public function documentos()
    {
        return $this->hasMany(Documentos_Administrativos::class,'carpeta_id');
    }
}
