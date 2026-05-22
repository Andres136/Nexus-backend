<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LlamadoAtencion extends Model
{
    use SoftDeletes;

    protected $table = 'llamados_atencion';

    protected $fillable = [
        'uuid', 'user_id', 'contratacion_id', 'tipo', 'titulo',
        'detalle', 'minutos', 'fecha_hecho', 'severidad', 'generado_por',
    ];

    protected $casts = [
        'uuid'        => 'string',
        'fecha_hecho' => 'date',
        'minutos'     => 'integer',
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

    public function generadoPor()
    {
        return $this->belongsTo(User::class, 'generado_por');
    }
}
