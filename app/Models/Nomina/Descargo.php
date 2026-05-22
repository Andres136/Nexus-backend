<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Descargo extends Model
{
    use SoftDeletes;

    protected $table = 'descargos';

    protected $fillable = [
        'uuid', 'user_id', 'contratacion_id', 'llamado_atencion_id',
        'tipo_descargo', 'fecha_hecho', 'descripcion', 'generado_por',
    ];

    protected $casts = [
        'uuid'        => 'string',
        'fecha_hecho' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = Str::uuid());
    }

    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contratacion()
    {
        return $this->belongsTo(Contratacion::class, 'contratacion_id')->withTrashed();
    }

    public function llamadoAtencion()
    {
        return $this->belongsTo(LlamadoAtencion::class, 'llamado_atencion_id');
    }

    public function generadoPor()
    {
        return $this->belongsTo(User::class, 'generado_por');
    }
}
