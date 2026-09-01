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
       'tipo_meta', // 'mayor' o 'menor'
       // --- Balanced Scorecard ---
       'perspectiva',           // clave de bsc_perspectivas
       'objetivo_estrategico',
       'unidad',                // %, $, dias, ratio
       'calculo_key',           // resolver automático; null => manual
       'orden',
   ];

    //funcion para relacionar indicadores con departamentos
    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    }

    //funcion para relacionar indicadores con su perspectiva BSC
    public function bscPerspectiva()
    {
        return $this->belongsTo(\App\Models\Bsc\BscPerspectiva::class, 'perspectiva', 'clave');
    }

    // Indicador cuyo valor lo calcula el motor BSC (no lo teclea nadie)
    public function esAutomatico(): bool
    {
        return !empty($this->calculo_key);
    }

    public function scopeClasificados($query)
    {
        return $query->whereNotNull('perspectiva');
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