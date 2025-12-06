<?php

namespace App\Models;

use App\Models\Roles\Permission;
use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{

protected $table = 'roles';



    protected $fillable = [
        'nombre',
       
    ];

    // funcion para relacionar roles con usuarios
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    // funcion para relacionar roles con permisos (many to many)
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission', 'role_id', 'permission_id');
    }

}
