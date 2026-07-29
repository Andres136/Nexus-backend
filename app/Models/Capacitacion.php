<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Capacitacion extends Model
{
    use SoftDeletes;

    protected $table = 'capacitaciones';

    protected $fillable = [
        'uuid',
        'user_id',
        'titulo',
        'descripcion',
        'fecha_realizacion',
        'hora_inicio',
        'hora_fin',
        'lugar',
        'modalidad',
        'estado',
    ];

    protected $casts = [
        'fecha_realizacion' => 'date:Y-m-d',
        'uuid' => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid ??= Str::uuid()->toString();
        });
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function encuestas()
    {
        return $this->hasMany(CapacitacionEncuesta::class, 'capacitacion_id');
    }

    public function acta()
    {
        return $this->hasOne(CapacitacionActa::class, 'capacitacion_id');
    }
}
