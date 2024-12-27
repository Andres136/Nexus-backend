<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\MacroProcesoController;
use App\Http\Controllers\ProcesoController;
use App\Http\Controllers\TareaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('macroprocesos',MacroProcesoController::class);
Route::apiResource('departamentos',DepartamentoController::class);
Route::apiResource('estados',EstadoController::class);
Route::apiResource('users',AuthController::class);
Route::apiResource('procesos',ProcesoController::class);
Route::apiResource('documentos',DocumentoController::class);
Route::apiResource('tareas',TareaController::class);
Route::apiResource('errores',ErrorController::class);
