<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estados extends Model
{
    protected $fillable =
     ['nombre'];    


     //funcion para relacionar estados con usuarios
    public function users(){
        return $this->hasMany(User::class, 'estado_id');
    }
}
