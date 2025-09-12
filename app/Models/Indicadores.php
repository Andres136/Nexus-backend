<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Indicadores extends Model
{
   protected $table = 'indicadores_procesos';
   protected $fillable = [
       'departamento_id',
       'formula',
       'meta',
       'frecuencia',
       'nombre',
       'descripcion',
       'user_id',
       'tipo_meta' // 'mayor' o 'menor'
   ];

    //funcion para relacionar indicadores con departamentos
    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    }
    //funcion para relacionar indicadores con usuarios
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');   
    }
    //funcion para relacionar indicadores con registros
    public function registros()
    {
        return $this->hasMany(RegistroIndicador::class, 'indicador_id');

    }


}