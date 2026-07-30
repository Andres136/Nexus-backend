<?php

namespace App\Models\comunicaciones;

use Illuminate\Database\Eloquent\Model;

class TipoPost extends Model
{
    protected $table = 'tipos_post';

    protected $fillable = [
        'nombre',
    ];

    public function publicaciones()
    {
        return $this->hasMany(PublicacionMarketing::class, 'tipo_post_id');
    }
}
