<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UsersFacePhoto extends Model
{
    use SoftDeletes;

    protected $table = 'users_face_photos';

    protected $fillable = [
        'uuid',
        'users_id',
        'photo',
    ];

    protected $casts = [
        'photo' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function empleado()
    {
        return $this->belongsTo(\App\Models\User::class, 'users_id');
    }
}
