<?php

namespace App\Models\Hseq;

use Illuminate\Database\Eloquent\Model;

class ClausulaIso extends Model
{
    protected $table = 'clausulas_iso';

    protected $fillable = [
        'norma_iso_id',
        'codigo',
        'descripcion',
        'activa',
    ];

    public function norma()
    {
        return $this->belongsTo(NormaIso::class, 'norma_iso_id');
    }
}
