<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Crm\ClienteController;
use App\Http\Controllers\Crm\OrdenCompraController;
use App\Http\Controllers\Crm\OrdenCompraDetallesController;
use App\Http\Controllers\Crm\SeguimientoController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\MacroProcesoController;
use App\Http\Controllers\NotificacionOrdenController;
use App\Http\Controllers\PqrController;
use App\Http\Controllers\ProcesoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\UpdateDepartamentoController;
use App\Http\Controllers\UsuarioController;
use App\Models\Pqr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:sanctum')->group(function(){
   Route::get('/user', function(Request $request){
        return $request->user();
    });  

    Route::post('/logout', [AuthController::class,'logout']);
  Route::put('/users/{id}/estado',[AuthController::class,'desactivar']);
Route::get('/clientes-registro-user',[ClienteController::class,'clientesUsuario']);

Route::apiResource('clientes',ClienteController::class);
Route::get('clientes-todos',[ClienteController::class,'clientesTodos']);


Route::apiResource('orden-compras',OrdenCompraController::class);
Route::post('/orden-trabajo/{id}', [OrdenCompraController::class, 'generarOrdenTrabajo']);

Route::get('ordenes-trabajo',[OrdenCompraController::class,'obtenerOrdenesTrabajo']);
Route::get('/notificaciones', [NotificacionOrdenController::class, 'listarNotificaciones']);

});  



Route::get('/notificar-ordenes', [NotificacionOrdenController::class, 'notificarOrdenes']);


Route::apiResource('users',AuthController::class);
Route::get('procesos/departamento/{departamento_id}', [ProcesoController::class, 'index']);
//Descargar documento
Route::get('documentos/descargar/{id}', [DocumentoController::class, 'download']);
Route::apiResource('macroprocesos',MacroProcesoController::class);
Route::apiResource('departamentos',DepartamentoController::class);
Route::apiResource('estados',EstadoController::class);
Route::get('usuarios/departamento/{departamento_id}', [AuthController::class, 'DeparamentosUsuario']);
Route::get('/documentacion/{id}', [DocumentoController::class, 'index']);
Route::get('/errores/kpi', [ErrorController::class, 'kpiErrores']);
Route::post('login',[AuthController::class,'login']);
Route::apiResource('procesos',ProcesoController::class);
Route::apiResource('documentos',DocumentoController::class);
Route::apiResource('tareas',TareaController::class);
Route::put('tareas/estado/{id}/',[TareaController::class,'destroy']);
Route::apiResource('errores',ErrorController::class);
Route::apiResource('roles',RolController::class);
Route::put('update/{id}',[UsuarioController::class,'update']);
Route::post('pqr',[PqrController::class,'store']);
Route::post('contacto',[PqrController::class,'contacto']);
//rutas crm

Route::apiResource('clientes/{cliente}/seguimientos',SeguimientoController::class);
Route::get('/seguimientos',[SeguimientoController::class,'index']);






