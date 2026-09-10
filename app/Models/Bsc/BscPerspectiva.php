<?php

namespace App\Models\Bsc;

use App\Models\Departamentos;
use App\Models\Indicadores;
use Illuminate\Database\Eloquent\Model;

class BscPerspectiva extends Model
{
    protected $table = 'bsc_perspectivas';

    protected $fillable = [
        'clave',
        'nombre',
        'color',
        'icono',
        'orden',
    ];

    protected $appends = ['icono_url'];

    public function getIconoUrlAttribute(): ?string
    {
        return $this->icono ? url('storage/' . $this->icono) : null;
    }

    public function indicadores()
    {
        return $this->hasMany(Indicadores::class, 'perspectiva', 'clave');
    }

    /** Departamentos responsables de esta perspectiva (1..N). */
    public function departamentos()
    {
        return $this->belongsToMany(
            Departamentos::class,
            'bsc_perspectiva_departamento',
            'perspectiva_id',
            'departamento_id'
        )->withTimestamps();
    }
}
