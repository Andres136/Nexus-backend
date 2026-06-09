<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Comision extends Model
{
    use SoftDeletes;

    protected $table = 'comisiones';

    protected $fillable = [
        'uuid',
        'user_id',
        'periodo_inicio',
        'periodo_fin',
        'concepto',
        'valor',
        'status',
        'observacion',
        'registrado_por',
        'autorizado_por',
        'fecha_gestion',
        'observacion_gestion',
        'nomina_id',
        'liquidacion_retiro_id',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
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

    public function liquidacionRetiro()
    {
        return $this->belongsTo(LiquidacionRetiro::class, 'liquidacion_retiro_id');
    }
}
