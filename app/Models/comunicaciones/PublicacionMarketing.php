<?php

namespace App\Models\comunicaciones;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PublicacionMarketing extends Model
{
    protected $table = 'publicaciones_marketing';

    protected $fillable = [
        'titulo',
        'red_social_id',
        'tipo_post_id',
        'fecha',
        'estado',
        'link',
        'descripcion',
        'responsable_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function redSocial()
    {
        return $this->belongsTo(RedSocial::class, 'red_social_id');
    }

    public function tipoPost()
    {
        return $this->belongsTo(TipoPost::class, 'tipo_post_id');
    }
}
