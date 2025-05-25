<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Crm\CarpetaController;
use App\Http\Controllers\Crm\ClienteController;
use App\Http\Controllers\Crm\CotizacionController;
use App\Http\Controllers\Crm\DashboardController;
use App\Http\Controllers\Crm\DocumentosAdministrativosController;
use App\Http\Controllers\Crm\DocumentoVehiculoController;
use App\Http\Controllers\Crm\EntregaProveedorController;
use App\Http\Controllers\Crm\InspeccionController;
use App\Http\Controllers\Crm\MantenimientoController;
use App\Http\Controllers\Crm\OrdenCompraController;
use App\Http\Controllers\Crm\OrdenCompraDetallesController;
use App\Http\Controllers\Crm\OrdenCompraProveedorController;
use App\Http\Controllers\Crm\ordenTrabajoController;
use App\Http\Controllers\Crm\ProveedorController;
use App\Http\Controllers\Crm\SeguimientoController;
use App\Http\Controllers\Crm\SiigoController;
use App\Http\Controllers\Crm\SiigoGlobalController;
use App\Http\Controllers\Crm\VehiculoController;
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



Route::middleware('auth:sanctum')->group(function () {
  Route::get('/user', function (Request $request) {
    return $request->user();
  });  Route::apiResource('users', AuthController::class);
  Route::post('/logout', [AuthController::class, 'logout']);
  Route::put('/users/{id}/estado', [AuthController::class, 'desactivar']);
  Route::get('/clientes-registro-user', [ClienteController::class, 'clientesUsuario']);
  Route::apiResource('clientes', ClienteController::class);
  Route::get('clientes-todos', [ClienteController::class, 'clientesTodos']);
  Route::apiResource('orden-compras', OrdenCompraController::class);
  Route::get('/notificaciones', [NotificacionOrdenController::class, 'listarNotificaciones']);

  Route::post('/orden-trabajo/{id}', [OrdenCompraController::class, 'generarOrdenTrabajo']);
  Route::get('tareas-vencidas', [NotificacionOrdenController::class, 'EnviarTaskVencida']);
  Route::apiResource('macroprocesos', MacroProcesoController::class);
  Route::apiResource('estados', EstadoController::class);
  Route::get('usuarios/departamento/{departamento_id}', [AuthController::class, 'DepartamentoUsuario']);
  Route::get('/documentacion/{id}', [DocumentoController::class, 'index']);
  Route::get('/errores/kpi', [ErrorController::class, 'kpiErrores']);
  Route::apiResource('clientes/{cliente}/seguimientos', SeguimientoController::class);

  
  Route::get('ordenes-compra-facturar', [OrdenCompraController::class, 'ordenesFacturar']);
  Route::apiResource('registrar-documentacion', DocumentosAdministrativosController::class);
  Route::get('/notificar-ordenes', [NotificacionOrdenController::class, 'notificarOrdenes']);

 Route::get('stock', [SiigoController::class, 'stock']);
   
  Route::get('stock-global', [SiigoGlobalController::class, 'stock']);
  Route::patch('tareas/estado/{id}/', [TareaController::class, 'update']);
  Route::apiResource('tareas', TareaController::class);
  Route::put('/pqrs/{id}/estado', [PqrController::class, 'cambiarEstado']);
  Route::get('notifications-pqrs/pqr', [NotificacionOrdenController::class, 'notificacionesPqrs']);
  //vehiculos
Route::apiResource('vehiculos', VehiculoController::class);
Route::apiResource('mantenimientos', MantenimientoController::class);
Route::apiResource('inspecciones', InspeccionController::class);
Route::apiResource('documentos-vehiculos', DocumentoVehiculoController::class);
Route::get('dashboard-vehiculos', [VehiculoController::class, 'getDashboardVehiculos']);
Route::get('vehiculos-all', [VehiculoController::class, 'vehiculosAll']);
Route::apiResource('ordenes-compra-proveedor', OrdenCompraProveedorController::class);
Route::post('/notificaciones/marcar-leidas', [NotificacionOrdenController::class, 'marcarTodasComoLeidas']);
Route::get('/notifications-pqrs/pqr', [NotificacionOrdenController::class, 'listarNotificacionesPqrs']);
Route::apiResource('proveedores',ProveedorController::class);

Route::apiResource('/cotizaciones', CotizacionController::class);
Route::get('/tareasKpi', [TareaController::class, 'resumenMensualFiltrado']);


Route::put('orden-compras/{id}', [OrdenCompraController::class, 'update']);
Route::get('mis-ordenes', [OrdenCompraController::class, 'misOrdenes']);

Route::get("/mis-cotizaciones", [CotizacionController::class, 'misCotizaciones']);
Route::get('ordenes-compra/{id}', [OrdenCompraController::class, 'show']);
Route::get('orden-compras/{id}/edit', [OrdenCompraController::class, 'edit']);
Route::post('entregas-proveedor', [EntregaProveedorController::class, 'store']);
});
Route::delete('orden-compras/{id}', [OrdenCompraController::class, 'destroy']);


Route::get('/orden-trabajo/{id}', [ordenTrabajoController::class, 'show']);

Route::get('dashboard/monthly', [DashboardController::class, 'getMonthlyStats']);

Route::get('/orden-compras/{id}/pdf', [OrdenCompraController::class, 'generarPdf']);
Route::get('/cotizaciones/{id}/pdf', [CotizacionController::class, 'descargarPDF']);
Route::get("/usuarios-comerciales",[ClienteController::class,'usuariosComerciales']);

Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);
route::get('dashboard-entregas-hoy', [DashboardController::class, 'ordenesEntreganHoy']);
Route::apiResource('departamentos', DepartamentoController::class);
Route::get('documentos/descargar/{id}', [DocumentoController::class, 'download']);
Route::apiResource('procesos', ProcesoController::class);
Route::apiResource('documentos', DocumentoController::class);


Route::apiResource('errores', ErrorController::class);
Route::apiResource('roles', RolController::class);
Route::put('update/{id}', [UsuarioController::class, 'update']);
Route::post('pqr', [PqrController::class, 'store']);
Route::get('pqrs', [PqrController::class, 'index']);
Route::post('contacto', [PqrController::class, 'contacto']);
//rutas crm
Route::get('/seguimientos', [SeguimientoController::class, 'index']);

Route::get('ordenes-trabajo', [OrdenCompraController::class, 'obtenerOrdenesTrabajo']);
Route::get('procesos/departamento/{departamento_id}', [ProcesoController::class, 'index']);

Route::get('download/{id}', [DocumentosAdministrativosController::class, 'downloand']);
Route::delete('documentos-administrativos/{id}', [DocumentosAdministrativosController::class, 'destroy']);
Route::apiResource('carpetas', CarpetaController::class);
Route::post('login', [AuthController::class, 'login'])->name('login');






