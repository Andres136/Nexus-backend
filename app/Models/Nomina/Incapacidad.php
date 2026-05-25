<?php

namespace App\Models\Nomina;

use App\Models\Crm\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Incapacidad extends Model
{
    use SoftDeletes;

    protected $table = 'incapacidades';

    protected $fillable = [
        'uuid',
        'tipo_incapacidad',
        'identidad_medica_id',
        'inicio',
        'fin',
        'soporte',
        'status',
        'user_id',
        'user_reviso_id',
        'estado_revision',
        'observacion_revision',
        'fecha_revision',
    ];

    protected $casts = [
        'inicio'  => 'date',
        'fin'     => 'date',
        'status'  => 'boolean',
        'fecha_revision' => 'datetime',
        'uuid'    => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    // Relaciones
    public function empleado()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function revisor()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_reviso_id');
    }

// App\Models\Nomina\Incapacidad.php

public function entidadMedica()
{
    return $this->belongsTo(
        \App\Models\Nomina\SeguridadSocial::class,
        'identidad_medica_id',
        'id'
    )->withTrashed(); // si seguridad social usa soft delete
}


}
