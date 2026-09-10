<?php

namespace App\Models\comunicaciones;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VentanaWebRegistro extends Model
{
    protected $table = 'ventana_web_registros';

    protected $fillable = [
        'uuid',
        'ventana_web_id',
        'nombre',
        'correo',
        'telefono',
        'mensaje',
        'leido',
    ];

    protected $casts = [
        'uuid' => 'string',
        'leido' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid ??= Str::uuid()->toString();
        });
    }

    public function ventana(): BelongsTo
    {
        return $this->belongsTo(VentanaWeb::class, 'ventana_web_id');
    }
}
