<?php

namespace App\Services\Nomina;

use App\Models\Nomina\TipoContrato;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TipoContratoService
{
    public function getAll(): Collection
    {
        return TipoContrato::where('activo', true)->get();
    }

    public function getById(string $uuid): TipoContrato
    {
        return TipoContrato::where('uuid', $uuid)->firstOrFail();
    }

public function create(array $data): TipoContrato
{
    /*
    |--------------------------------------------------------------------------
    | GENERAR CÓDIGO BASE
    |--------------------------------------------------------------------------
    */
    $baseCodigo = strtoupper(
        $data['codigo'] ??
        Str::slug(
            substr($data['nombre'], 0, 3),
            ''
        )
    );

    $codigo = $baseCodigo;
    $contador = 1;

    /*
    |--------------------------------------------------------------------------
    | EVITAR DUPLICADOS
    |--------------------------------------------------------------------------
    */
    while (
        TipoContrato::where(
            'codigo',
            $codigo
        )->exists()
    ) {
        $codigo =
            $baseCodigo . $contador;

        $contador++;
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR REGISTRO
    |--------------------------------------------------------------------------
    */
    return TipoContrato::create([
        'nombre' => $data['nombre'],

        'codigo' => $codigo,

        'descripcion' =>
            $data['descripcion'] ?? null,

        'activo' =>
            $data['activo'] ?? true,
    ]);
}

public function update(string $uuid, array $data): TipoContrato
{
    $tipoContrato = TipoContrato::where('uuid', $uuid)
        ->firstOrFail();

    $codigo = strtoupper(
        $data['codigo'] ??
        $tipoContrato->codigo ??
        Str::slug(substr($data['nombre'], 0, 3), '')
    );

    $tipoContrato->update([
        'nombre'      => $data['nombre'] ?? $tipoContrato->nombre,
        'codigo'      => $codigo,
        'descripcion' => $data['descripcion'] ?? $tipoContrato->descripcion,
        'activo'      => $data['activo'] ?? $tipoContrato->activo,
    ]);

    return $tipoContrato->fresh();
}

    public function delete(string $uuid): void
    {
        $tipoContrato = TipoContrato::where('uuid', $uuid)->firstOrFail();
        $tipoContrato->delete();
    }
}