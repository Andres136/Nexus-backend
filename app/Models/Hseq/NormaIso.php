<?php

namespace App\Models\Hseq;

use Illuminate\Database\Eloquent\Model;

class NormaIso extends Model
{
    protected $table = 'normas_iso';

    protected $fillable = [
        'nombre',
        'activa',
    ];

    public function clausulas()
    {
        return $this->hasMany(ClausulaIso::class);
    }
}
