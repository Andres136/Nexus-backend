<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroIndicador extends Model
{
    protected $table = 'registro_indicadores';
    protected $fillable = [
         'indicador_id',
         'fecha',
         'valor',
         'observaciones',
         'documento',
         'user_id'
    ];
    
     //funcion para relacionar registros con indicadores
     public function indicador()
     {
          return $this->belongsTo(Indicadores::class, 'indicador_id');
     }
     //funcion para relacionar registros con usuarios
     public function user()
     {
          return $this->belongsTo(User::class, 'user_id');   
     }
}
