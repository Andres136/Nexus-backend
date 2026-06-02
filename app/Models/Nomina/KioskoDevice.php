<?php

namespace App\Models\Nomina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KioskoDevice extends Model
{
    use SoftDeletes;

    protected $table = 'kiosko_devices';

    protected $fillable = [
        'uuid',
        'sede_id',
        'name',
        'code',
        'ip_adres',
        'descripcion',
        'bodega_id',
        'tipo_registros_id',
        'activation_token_hash',
        'activation_expires_at',
        'activation_used_at',
        'device_session_token_hash',
        'device_fingerprint_hash',
        'activated_at',
        'last_seen_at',
        'last_ip',
        'status',
        'revoked_at',
        'guest_token_hash',
        'guest_expires_at',
    ];

    protected $casts = [
        'activation_expires_at' => 'datetime',
        'activation_used_at'    => 'datetime',
        'activated_at'          => 'datetime',
        'last_seen_at'          => 'datetime',
        'revoked_at'            => 'datetime',
        'guest_expires_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function sede()
    {
        return $this->belongsTo(\App\Models\Crm\Sede::class, 'sede_id');
    }

    public function bodega()
    {
        return $this->belongsTo(\App\Models\Crm\bodega::class, 'bodega_id');
    }

    public function tipoRegistro()
    {
        return $this->belongsTo(TipoRegistro::class, 'tipo_registros_id');
    }
}
