<?php

namespace App\Models\contabilidad;

use App\ImpuestoOperacionEnum;
use Illuminate\Database\Eloquent\Model;

class Impuesto extends Model
{
    protected $table = 'impuestos';
    protected $fillable = [
        'nombre',
        'porcentaje',
        'operacion',
    ];

    protected function casts(): array
    {
        return [
            'operacion' => ImpuestoOperacionEnum::class,
        ];
    }

    public function calcularMonto(float $base): float
    {
        $operacion = $this->operacion ?? ImpuestoOperacionEnum::SUMA;

        return $base * ((float) $this->porcentaje / 100) * $operacion->factor();
    }

    public function facturaCompraImpuestos()
    {
        return $this->hasMany(FacturaCompraImpuesto::class, 'impuestos_id');
    }
}
