<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class NovedadRetroactiva extends Model
{
    use SoftDeletes;

    protected $table = 'novedades_retroactivas';

    protected $fillable = [
        'uuid',
        'user_id',
        'fecha_origen',
        'aplicar_desde',
        'aplicar_hasta',
        'tipo',
        'concepto',
        'valor',
        'status',
        'observacion',
        'registrado_por',
        'autorizado_por',
        'fecha_gestion',
        'observacion_gestion',
        'nomina_id',
    ];

    protected $casts = [
        'fecha_origen' => 'date',
        'aplicar_desde' => 'date',
        'aplicar_hasta' => 'date',
        'valor' => 'decimal:2',
        'fecha_gestion' => 'datetime',
        'uuid' => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function nomina()
    {
        return $this->belongsTo(Nomina::class, 'nomina_id');
    }
}
