<?php

namespace App\Services\Nomina;

use App\Models\Nomina\TransacionalRegistro;
use App\Http\Requests\Nomina\StoreTransacionalRegistroRequest;
use App\Http\Requests\Nomina\UpdateTransacionalRegistroRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TransacionalRegistroService
{
    public function getAll(): Collection
    {
        return TransacionalRegistro::with([
            'empleado',
            'kioskoDevice',
            'tipoMarcacion',
        ])->get();
    }

    public function getByUuid(string $uuid): TransacionalRegistro  // ← getById(int $id) → getByUuid(string $uuid)
    {
        return TransacionalRegistro::with([
            'empleado',
            'kioskoDevice',
            'tipoMarcacion',
        ])
        ->where('uuid', $uuid)                                     // ← findOrFail($id) → where + firstOrFail
        ->firstOrFail();
    }

    public function getByUser(int $userId): Collection             // ← este no cambia, usa userId no id del registro
    {
        return TransacionalRegistro::with(['tipoMarcacion', 'kioskoDevice'])
            ->where('users_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(StoreTransacionalRegistroRequest $request): TransacionalRegistro
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            if ($request->hasFile('foto_referencia')) {
                $data['foto_referencia'] = $request->file('foto_referencia')
                    ->store('nomina/marcaciones', 'public');
            }

            $registro = TransacionalRegistro::create($data);

            Log::info('Marcación registrada', [
                'uuid'    => $registro->uuid,                      // ← 'id' → 'uuid'
                'user_id' => $registro->users_id,
                'hora'    => $registro->marked_ad,
            ]);

            return $registro;
        });
    }

    public function update(UpdateTransacionalRegistroRequest $request, string $uuid): TransacionalRegistro  // ← int $id → string $uuid
    {
        return DB::transaction(function () use ($request, $uuid) {
            $registro = $this->getByUuid($uuid);                   // ← getById($id) → getByUuid($uuid)
            $data     = $request->validated();

            if ($request->hasFile('foto_referencia')) {
                if ($registro->foto_referencia) {
                    Storage::disk('public')->delete($registro->foto_referencia);
                }
                $data['foto_referencia'] = $request->file('foto_referencia')
                    ->store('nomina/marcaciones', 'public');
            }

            $registro->update($data);

            Log::info('Marcación actualizada', ['uuid' => $registro->uuid]);  // ← 'id' → 'uuid'

            return $registro->fresh([                              // ← agregado fresh() con relaciones
                'empleado',
                'kioskoDevice',
                'tipoMarcacion',
            ]);
        });
    }

    public function delete(string $uuid): void                     // ← int $id → string $uuid
    {
        DB::transaction(function () use ($uuid) {
            $registro = $this->getByUuid($uuid);                   // ← getById($id) → getByUuid($uuid)

            if ($registro->foto_referencia) {
                Storage::disk('public')->delete($registro->foto_referencia);
            }

            $registro->delete();

            Log::info('Marcación eliminada', ['uuid' => $registro->uuid]);  // ← 'id' → 'uuid'
        });
    }
}