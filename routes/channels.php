<?php

use App\Models\Nomina\Cuestionario;
use App\Models\Nomina\PostulacionConvocatoria;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Sala de espera / cuestionario en vivo de una convocatoria: puede entrar
// quien lo creó (RRHH) o cualquier postulante de esa convocatoria.
Broadcast::channel('convocatoria-cuestionario.{cuestionarioId}', function ($user, $cuestionarioId) {
    $cuestionario = Cuestionario::find($cuestionarioId);

    if (! $cuestionario) {
        return false;
    }

    $esCreador = (int) $cuestionario->creado_por === (int) $user->id;
    $esPostulante = PostulacionConvocatoria::where('convocatoria_id', $cuestionario->convocatoria_id)
        ->where('user_id', $user->id)
        ->exists();

    if (! $esCreador && ! $esPostulante) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => trim("{$user->name} {$user->apellidos}"),
        'es_creador' => $esCreador,
    ];
});
