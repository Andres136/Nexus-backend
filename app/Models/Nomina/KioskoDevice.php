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
        return $this->belongsTo(\App\Models\Crm\Bodega::class, 'bodega_id');
    }

    public function tipoRegistro()
    {
        return $this->belongsTo(TipoRegistro::class, 'tipo_registros_id');
    }
}
