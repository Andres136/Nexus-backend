<?php

namespace App\Models\comunicaciones;

use Illuminate\Database\Eloquent\Model;

class RedSocial extends Model
{
    protected $table = 'redes_sociales';

    protected $fillable = [
        'nombre',
    ];

    public function publicaciones()
    {
        return $this->hasMany(PublicacionMarketing::class, 'red_social_id');
    }
}
