<?php

namespace App\Models\Roles;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permission';

    protected $fillable = [
        'name',
        'path',
        'module',
        'enabled',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission', 'permission_id', 'role_id');
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permission', 'permission_id', 'user_id');
    }
}
