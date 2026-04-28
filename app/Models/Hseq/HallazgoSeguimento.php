<?php

namespace App\Models\Hseq;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HallazgoSeguimento extends Model
{
    protected $table = 'hallazgos_seguimiento';

    protected $fillable = [
        'hallazgo_id',
        'usuario_id',
        'observacion',
        'fecha',
    ];

    public function hallazgo()
    {
        return $this->belongsTo(HallazgoNovedad::class, 'hallazgo_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
