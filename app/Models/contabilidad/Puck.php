<?php

namespace App\Models\contabilidad;

use Illuminate\Database\Eloquent\Model;

class Puck extends Model
{
    protected $table = 'puck';
    protected $fillable = [
        'parent_id',
        'nombre',
        'numero',
        'nivel',
        'naturaleza',
        'descripcion',
        'dinamica',
        'permite_movimiento',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'permite_movimiento' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function facturaCompras()
    {
        return $this->hasMany(FacturaCompra::class, 'pucks_id');
    }
}
