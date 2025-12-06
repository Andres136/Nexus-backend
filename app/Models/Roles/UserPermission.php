<?php

namespace App\Models\Roles;


use Illuminate\Database\Eloquent\Relations\Pivot;

class UserPermission extends Pivot
{
    protected $table = 'user_permission';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'permission_id',
    ];
}