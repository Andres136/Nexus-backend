<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Macroprocesos extends Model
{
    protected $fillable = ['nombre'];
    //RELACION UNO A MUCHOS
    public function departamentos()
    {
        return $this->hasMany(Departamentos::class, 'macroprocesos_id');
    }
}
