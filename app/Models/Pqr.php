<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pqr extends Model
{
    protected $fillable = [
        'nombre',
        'empresa',
        'email',
        'telefono',
        'mensaje',
    ];

    public function estado()
    {
        return $this->belongsTo(Estados::class);
    }
}
