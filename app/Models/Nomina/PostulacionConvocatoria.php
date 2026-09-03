<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PostulacionConvocatoria extends Model
{
    protected $table = 'postulaciones_convocatoria';

    protected $fillable = [
        'convocatoria_id',
        'user_id',
        'cargo_interes',
    ];

    public function convocatoria()
    {
        return $this->belongsTo(Convocatoria::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
