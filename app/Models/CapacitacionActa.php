<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CapacitacionActa extends Model
{
    protected $table = 'capacitacion_actas';

    protected $fillable = [
        'uuid', 'capacitacion_id', 'elaborada_por', 'numero', 'titulo',
        'objetivo', 'desarrollo', 'compromisos', 'conclusiones', 'publicada_at',
    ];

    protected $casts = [
        'compromisos' => 'array',
        'publicada_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->uuid ??= Str::uuid()->toString());
    }

    public function capacitacion() { return $this->belongsTo(Capacitacion::class); }
    public function elaborador() { return $this->belongsTo(User::class, 'elaborada_por'); }
    public function envios() { return $this->hasMany(CapacitacionActaEnvio::class, 'acta_id'); }
}
