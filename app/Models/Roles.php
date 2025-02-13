<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    // funcion para relacionar roles con usuarios
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }


}
