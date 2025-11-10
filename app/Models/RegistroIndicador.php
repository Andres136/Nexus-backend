<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RegistroIndicador extends Model
{
    protected $table = 'registro_indicadores';

protected $appends = ['documento_url'];
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

     public function getDocumentoUrlAttribute()
{
    return $this->documento ? Storage::url($this->documento) : null;
}
}
