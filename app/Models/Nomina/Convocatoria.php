<?php

namespace App\Models\Nomina;

use App\Models\Crm\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Convocatoria extends Model
{
    protected $fillable = [
        'uuid',
        'titulo',
        'descripcion',
        'imagen',
        'sede_id',
        'activa',
        'creado_por',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    protected $appends = ['imagen_url'];

    protected static function booted(): void
    {
        static::creating(function (self $convocatoria) {
            $convocatoria->uuid ??= (string) Str::uuid();
        });
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function postulaciones()
    {
        return $this->hasMany(PostulacionConvocatoria::class);
    }

    public function cuestionario()
    {
        return $this->hasOne(Cuestionario::class);
    }

    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen ? Storage::url($this->imagen) : null;
    }
}
