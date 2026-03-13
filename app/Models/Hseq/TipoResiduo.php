<?php

namespace App\Models\Hseq;

use Illuminate\Database\Eloquent\Model;

class TipoResiduo extends Model
{
    protected $table = 'tipo_residuos';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function residuos()
    {
        return $this->hasMany(Residuo::class, 'tipo_residuo_id');
    }
}
