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

    public function getById(int $id): TransacionalRegistro
    {
        return TransacionalRegistro::with([
            'empleado',
            'kioskoDevice',
            'tipoMarcacion',
        ])->findOrFail($id);
    }

    public function getByUser(int $userId): Collection
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
                'id'      => $registro->id,
                'user_id' => $registro->users_id,
                'hora'    => $registro->marked_ad,
            ]);

            return $registro;
        });
    }

    public function update(UpdateTransacionalRegistroRequest $request, int $id): TransacionalRegistro
    {
        return DB::transaction(function () use ($request, $id) {
            $registro = $this->getById($id);
            $data     = $request->validated();

            if ($request->hasFile('foto_referencia')) {
                if ($registro->foto_referencia) {
                    Storage::disk('public')->delete($registro->foto_referencia);
                }
                $data['foto_referencia'] = $request->file('foto_referencia')
                    ->store('nomina/marcaciones', 'public');
            }

            $registro->update($data);

            Log::info('Marcación actualizada', ['id' => $registro->id]);

            return $registro;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $registro = $this->getById($id);

            if ($registro->foto_referencia) {
                Storage::disk('public')->delete($registro->foto_referencia);
            }

            $registro->delete();

            Log::info('Marcación eliminada', ['id' => $registro->id]);
        });
    }
}
