<?php

namespace App\Services\Crm;

use App\Models\Crm\GestionCarteraHistorial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GestionCarteraHistorialService
{
    
public function registrarGestion($gestionCarteraId, $soportes = [], $observacion, $tipo, $fechaCompromiso = null)
{
   return DB::transaction(function () use ($gestionCarteraId, $soportes, $observacion, $tipo, $fechaCompromiso) {

    $gestion = GestionCarteraHistorial::create([
        'gestion_cartera_id' => $gestionCarteraId,
        'user_id' => auth()->id(),
        'observacion' => $observacion,
        'tipo' => $tipo,
        'fecha_compromiso' => $fechaCompromiso
    ]);

    if (!empty($soportes)) {
        foreach ($soportes as $archivo) {
            $ruta = $archivo->store('cartera_soportes', 'public');

            $gestion->soportes()->create([
                'archivo' => $ruta
            ]);
        }
    }

    return $gestion->load('soportes');
});
}


//UPDATE
public function actualizarGestion($gestionId, $soportes = [], $observacion, $tipo, $fechaCompromiso = null)
{
    $gestion = GestionCarteraHistorial::findOrFail($gestionId);
    $gestion->update([
        'observacion' => $observacion,
        'tipo' => $tipo,
        'fecha_compromiso' => $fechaCompromiso
    ]);

    // 🔥 ACTUALIZAR SOPORTES
    if (!empty($soportes)) {
        // Eliminar los soportes existentes
      foreach ($gestion->soportes as $soporte) {
    Storage::disk('public')->delete($soporte->archivo);
}

$gestion->soportes()->delete();

        // Agregar los nuevos soportes
        foreach ($soportes as $archivo) {
            $ruta = $archivo->store('cartera_soportes', 'public');

            $gestion->soportes()->create([
                'archivo' => $ruta
            ]);
        }
    }

    return $gestion->load('soportes');

}

// OBTENER HISTORIAL DE GESTIONES POR ID DE GESTION DE CARTERA
public function obtenerHistorialPorGestionCartera($gestionCarteraId)
{
    return GestionCarteraHistorial::with('soportes')
        ->where('gestion_cartera_id', $gestionCarteraId)
        ->orderBy('created_at', 'desc')
        ->get();
}

//Destroy
public function eliminarGestion($gestionId)
{
    $gestion = GestionCarteraHistorial::findOrFail($gestionId);
foreach ($gestion->soportes as $soporte) {
    Storage::disk('public')->delete($soporte->archivo);
}

$gestion->soportes()->delete();
$gestion->delete();
    return $gestion->delete();
}
}