<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Crm\DatoConductor;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\Sede;
use App\Models\Roles\Permission;
use App\Models\Roles\Role;
use App\Models\Roles\UserPermission;
use App\Models\Rutas\DeliveryEvent;
use App\Models\Rutas\DeliveryRecord;
use App\Models\Vsm\Alistamiento;
use App\Models\Vsm\AlistamientoUsuario;
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
    public function esResponsableDeSuDepartamento(): bool
    {
        return $this->departamento && $this->departamento->responsable_id === $this->id;
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
public function datosConductor()
{
    return $this->hasOne(DatoConductor::class);
}

// Relación con los eventos de entrega
public function deliveryEvents()
{
    return $this->hasMany(DeliveryEvent::class, 'usuario_id');
}

//Relacion con evetos de entrega como quien registra
public function deliveryRecords()
{
    return $this->hasMany(DeliveryRecord::class, 'usuario_id');
}

//Relacion con permisos a traves de roles


public function roles()
{
    return $this->belongsToMany(Role::class, 'user_role');
}

    
public function permissions()
{
 return $this->role? $this->role->permissions() : collect([]);

}

public function permisos ()
{
 return $this->belongsToMany(Permission::class, 'user_permission', 'user_id', 'permission_id')
 ->using(UserPermission::class);
}

public function alistamientos()
{
    return $this->belongsToMany(Alistamiento::class, 'alistamiento_usuario','usuario_id','alistamiento_id')
                ->using(AlistamientoUsuario::class)
                ->withTimestamps();
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
