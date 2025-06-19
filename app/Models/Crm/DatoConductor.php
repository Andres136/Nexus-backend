<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DatoConductor extends Model
{
    protected $table = 'datos_conductores';
    protected $fillable = [
        'user_id',
        'cedula',
        'licencia_conduccion',
        'tipo_licencia',
        'fecha_expedicion',
        'fecha_vencimiento',        
        'categoria',
        'grupo_sanguineo',
        'rut_archivo',
        'licencia_archivo',
        'comparendo_archivo',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
