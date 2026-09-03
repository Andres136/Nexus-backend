<?php

namespace App\Models\comunicaciones;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Certificacion extends Model
{
    use SoftDeletes;

    protected $table = 'certificaciones';

    protected $fillable = [
        'uuid',
        'titulo',
        'subtitulo',
        'descripcion',
        'logo',
        'activo',
    ];

    protected $casts = [
        'uuid' => 'string',
        'activo' => 'boolean',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid ??= Str::uuid()->toString();
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }
}
