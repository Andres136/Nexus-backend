<?php

namespace App\Models\comunicaciones;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VentanaWeb extends Model
{
    protected $table = 'ventana_web';

    protected $fillable = [
        'uuid',
        'titulo',
        'subtitulo',
        'contenido',
        'imagen',
        'activo',
        'boton_activo',
        'boton_texto',
    ];

    protected $casts = [
        'uuid' => 'string',
        'activo' => 'boolean',
        'boton_activo' => 'boolean',
    ];

    protected $appends = [
        'imagen_url',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid ??= Str::uuid()->toString();
        });
    }

    public function registros(): HasMany
    {
        return $this->hasMany(VentanaWebRegistro::class, 'ventana_web_id');
    }

    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen ? asset('storage/' . $this->imagen) : null;
    }

    /**
     * Devuelve la configuración única, creándola si aún no existe.
     */
    public static function singleton(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
