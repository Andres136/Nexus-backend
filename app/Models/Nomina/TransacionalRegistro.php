<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TransacionalRegistro extends Model
{
    use SoftDeletes;

    protected $table = 'transacional_registros';

    protected $fillable = [
        'uuid',
        'users_id',
        'kiosk_device_id',
        'tipo_marcacion_id',
        'foto_referencia',
        'marked_ad',
    ];

    protected $casts = [
        'marked_ad' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function empleado()
    {
        return $this->belongsTo(\App\Models\User::class, 'users_id');
    }

    public function kioskoDevice()
    {
        return $this->belongsTo(KioskoDevice::class, 'kiosk_device_id');
    }

    public function tipoMarcacion()
    {
        return $this->belongsTo(TipoRegistro::class, 'tipo_marcacion_id');
    }
}
