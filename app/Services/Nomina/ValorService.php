<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Valor;
use App\Http\Requests\Nomina\StoreValorRequest;
use App\Http\Requests\Nomina\UpdateValorRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValorService
{
    // =====================
    // TRAER TODOS
    // =====================
    public function getAll()
    {
        return Valor::all();
    }

    // =====================
    // TRAER UNO
    // =====================
    public function getByUuid(string $uuid): Valor                 
    {
        return Valor::where('uuid', $uuid)                       
            ->firstOrFail();
    }

    // =====================
    // CREAR
    // =====================
    public function store(StoreValorRequest $request): Valor
    {
        return DB::transaction(function () use ($request) {

            $valor = Valor::create($request->validated());

            Log::info('Valores creados', [
                'uuid'               => $valor->uuid,              
                'valor_hora_normal'  => $valor->valor_hora_normal,
            ]);

            return $valor;
        });
    }

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(UpdateValorRequest $request, string $uuid): Valor  
    {
        return DB::transaction(function () use ($request, $uuid) {

            $valor = $this->getByUuid($uuid);                   

            $valor->update($request->validated());

            Log::info('Valores actualizados', ['uuid' => $valor->uuid]);  

            return $valor->fresh();                               
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(string $uuid): bool                    
    {
        return DB::transaction(function () use ($uuid) {

            $valor = $this->getByUuid($uuid);                      

            $valor->delete();

            Log::info('Valores eliminados', ['uuid' => $valor->uuid]);  

            return true;
        });
    }
}