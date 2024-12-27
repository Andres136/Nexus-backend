<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Errores extends Model
{
    protected $fillable =[
        'descripcion', 
        'proceso_id'
    ];
}
