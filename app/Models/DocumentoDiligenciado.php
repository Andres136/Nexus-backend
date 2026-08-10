<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoDiligenciado extends Model
{
    protected $fillable = [
        'documento_id',
        'user_id',
        'archivo',
        'nombre_original',
        'observaciones',
    ];

    public function documento()
    {
        return $this->belongsTo(Documentos::class, 'documento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
