<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Crm\Orden_Compra;
use App\Models\Crm\Sede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;  // ← IMPORTA ESTO
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use  HasApiTokens,  Notifiable ;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'telefono',
        'password',
     'departamento_id', 
        'imagen', 
        'role_id',  
        'estado_id'
        ,'sede_id'
    ];

    //funcion para relacionar usuarios con documentos
    public function documentos()
    {
        return $this->hasMany(Documentos::class, 'user_id');
    }

    //funcion para relacionar usuarios con procesos
    public function procesos()
    {
        return $this->hasMany(Procesos::class, 'user_id');
    }

  //funcion para relacionar usuarios con departamentos
    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    }

    //funcion para relacionar usuarios con roles
    public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }

  
//funcion para relacionar usuarios con estados
    public function estado()
    {
        return $this->belongsTo(Estados::class, 'estado_id');
    }

    //funcion para relacionar usuarios con tareas
  
    public function tareas()
    {
        return $this->hasMany(Tareas::class, 'usuario_id');
    }

    //
    //funcion para relacionar usuarios con ordenes de compra
    public function ordenes()
    {
        return $this->hasMany(Orden_Compra::class, 'user_id');
    }
   

    //Relacion con la tabla sedes
    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
