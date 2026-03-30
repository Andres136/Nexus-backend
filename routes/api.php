<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\comunicaciones\PlantillaController;
use App\Http\Controllers\Crm\AlistamientoOtController;
use App\Http\Controllers\Crm\BodegaController;
use App\Http\Controllers\Crm\CarpetaController;
use App\Http\Controllers\Crm\CategoriaController;
use App\Http\Controllers\Crm\ClienteController;
use App\Http\Controllers\Crm\CotizacionController;
use App\Http\Controllers\Crm\DashboardController;
use App\Http\Controllers\Crm\DatoCondutorController;
use App\Http\Controllers\Crm\DocumentosAdministrativosController;
use App\Http\Controllers\Crm\DocumentoVehiculoController;
use App\Http\Controllers\Crm\EmpresaController;
use App\Http\Controllers\Crm\EntregaProveedorController;
use App\Http\Controllers\Crm\EventoController;
use App\Http\Controllers\Crm\GestionCarteraController;
use App\Http\Controllers\Crm\GestionPivoteCarteraController;
use App\Http\Controllers\Crm\InspeccionController;
use App\Http\Controllers\Crm\InventorieController;
use App\Http\Controllers\Crm\MantenimientoController;
use App\Http\Controllers\Crm\Orden_servicio\OrdenesServicioController;
use App\Http\Controllers\Crm\OrdenCompraController;
use App\Http\Controllers\Crm\OrdenCompraDetallesController;
use App\Http\Controllers\Crm\OrdenCompraProveedorController;
use App\Http\Controllers\Crm\ordenTrabajoController;
use App\Http\Controllers\Crm\procesoBolsasController;
use App\Http\Controllers\Crm\ProductController as CrmProductController;
use App\Http\Controllers\Crm\ProveedorController;
use App\Http\Controllers\Crm\RevisionComparendoController;
use App\Http\Controllers\Crm\SedeController;
use App\Http\Controllers\Crm\SeguimientoController;
use App\Http\Controllers\Crm\SiigoController;
use App\Http\Controllers\Crm\SiigoGlobalController;
use App\Http\Controllers\Crm\VehiculoController;
use App\Http\Controllers\Crm\VehiculoFotoController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\Hseq\ConsumoServicioController;
use App\Http\Controllers\Hseq\HallazgoNovedadController;
use App\Http\Controllers\Hseq\HseqDashboardController;
use App\Http\Controllers\Hseq\InspeccionHseqController;
use App\Http\Controllers\Hseq\PreguntaInspeccionController;
use App\Http\Controllers\Hseq\ResiduoController;
use App\Http\Controllers\Hseq\RespuestaInspeccionController;
use App\Http\Controllers\Hseq\TipoInspeccionController;
use App\Http\Controllers\Hseq\TipoResiduoController;
use App\Http\Controllers\Hseq\TipoServicioController;
use App\Http\Controllers\IndicadoresProcesosController;
use App\Http\Controllers\MacroProcesoController;
use App\Http\Controllers\NotificacionOrdenController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PqrController;
use App\Http\Controllers\ProcesoController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RegistroDiario\NovedadController;
use App\Http\Controllers\RegistroDiario\PreguntaController;
use App\Http\Controllers\RegistroDiario\RegistroDiarioController;
use App\Http\Controllers\RegistroDiario\VerificacionDiariaControllerr;
use App\Http\Controllers\RegistroIndicadoresController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\Rutas\DeliveryEventController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\Tic\AsignacionesController;
use App\Http\Controllers\Tic\MantenimientoEquiposController;
use App\Http\Controllers\Traslados\EnvioInternoController;
use App\Http\Controllers\Traslados\ResponsabilidadesController;
use App\Http\Controllers\Traslados\TrasladosBodegaController;
use App\Http\Controllers\UpdateDepartamentoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\Vsm\AlistamientoController;
use App\Http\Controllers\Vsm\AlistamientoTiempoController;
use App\Http\Controllers\Vsm\ForecastController;
use App\Http\Controllers\whatsapp\WhatsappWebhookController;
use App\Models\Pqr;
use App\Models\Registro_indicadores;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

Route::middleware('auth:sanctum')->group(function () {
  Route::get('/user', function (Request $request) {
    return $request->user();
  });  

  //Usuarios
  Route::apiResource('users', AuthController::class);
  Route::post('/logout', [AuthController::class, 'logout']);
  Route::put('/users/{id}/estado', [AuthController::class, 'desactivar']);

//RUTAS SESSION DE USUARIOS
Route::get('sessions', [SessionController::class, 'index']);
Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);
Route::delete('/sessions/others', [SessionController::class, 'destroyOthers']);

  //Clientes

  Route::get('/clientes-registro-user', [ClienteController::class, 'clientesUsuario']);
  Route::apiResource('clientes', ClienteController::class);
  Route::get('clientes-todos', [ClienteController::class, 'clientesTodos']);
  

  //ordenes de compra
  Route::apiResource('orden-compras', OrdenCompraController::class);
  Route::get('/notificaciones', [NotificacionOrdenController::class, 'listarNotificaciones']);
 Route::get('ordenes-compra-facturar', [OrdenCompraController::class, 'ordenesFacturar']);

  Route::post('/orden-trabajo/{id}', [OrdenCompraController::class, 'generarOrdenTrabajo']);
  Route::get('tareas-vencidas', [NotificacionOrdenController::class, 'EnviarTaskVencida']);
  Route::apiResource('macroprocesos', MacroProcesoController::class);
  Route::apiResource('estados', EstadoController::class);
  Route::get('usuarios/departamento/{departamento_id}', [AuthController::class, 'DepartamentoUsuario']);
  Route::get('/documentacion/{id}', [DocumentoController::class, 'index']);
  Route::get('/errores/kpi', [ErrorController::class, 'kpiErrores']);
  Route::apiResource('clientes/{cliente}/seguimientos', SeguimientoController::class);
  

  
 
  Route::apiResource('registrar-documentacion', DocumentosAdministrativosController::class);
  Route::post('/documentos/mover-obseletos/{id}', [DocumentoController::class, 'moverAObseletos']);


//Consumir api siigo
  Route::get('products-setas', [SiigoController::class, 'index']);
 Route::get('stock', [SiigoController::class, 'stock']);
   //Consumir api siigo global
  Route::get('products-global', [SiigoGlobalController::class, 'index']);
  Route::get('stock-global', [SiigoGlobalController::class, 'stock']);
  Route::patch('tareas/estado/{id}/', [TareaController::class, 'update']);
  Route::apiResource('tareas', TareaController::class);
  Route::put('/tareas/update/{id}', [TareaController::class, 'actualizarTarea']);
  Route::put('/pqrs/{id}/estado', [PqrController::class, 'cambiarEstado']);
  Route::get('notifications-pqrs/pqr', [NotificacionOrdenController::class, 'notificacionesPqrs']);
  //vehiculos
Route::apiResource('vehiculos', VehiculoController::class);
Route::apiResource('mantenimientos', MantenimientoController::class);
Route::apiResource('inspecciones', InspeccionController::class);
Route::apiResource('documentos-vehiculos', DocumentoVehiculoController::class);
Route::get('dashboard-vehiculos', [VehiculoController::class, 'getDashboardVehiculos']);
Route::get('vehiculos-all', [VehiculoController::class, 'vehiculosAll']);

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
Route::get('/ordenes-compra/faltantes/pendientes', [OrdenCompraController::class, 'verificarFaltantesPendientes']);
//KPIS DASHBOARD CLIENTES
Route::get('/dashboard/kpis', [DashboardController::class, 'kpis']);

Route::post('/ordenes-compra-proveedor/{id}/dividir', [OrdenCompraProveedorController::class, 'dividirOrden']);
Route::post('/ordenes-compra-proveedor/observaciones', [procesoBolsasController::class, 'storeObservacion']);
Route::put('/observaciones/{id}/estado', [procesoBolsasController::class, 'updateEstado']);

Route::get('/orden-trabajo/{id}', [ordenTrabajoController::class, 'show']);
Route::get('ordenes-trabajo', [OrdenCompraController::class, 'obtenerOrdenesTrabajo']);
//Generar pdf de la orden de trabajo
Route::get('/orden-trabajo/{id}/pdf', [ordenTrabajoController::class, 'generarPDF']);
//Ruta de alistamiento ot
Route::apiResource('alistamientos-ot', AlistamientoOtController::class);

//Marcar orden de trabajo como revisada
Route::post('/orden-trabajo/{id}/marcar-revisada', [ordenTrabajoController::class, 'marcarRevisada']);

Route::get('pqrs', [PqrController::class, 'index']);
Route::delete('/pqrs/{id}', [PqrController::class, 'destroy']);

Route::put('/pqrs/{id}/responder', [PqrController::class, 'responder']);

//Conductores
Route::apiResource('datos-conductores', DatoCondutorController::class);

Route::apiResource('/vehiculos/{vehiculo}/fotos',VehiculoFotoController::class);
Route::get('/usuarios/all', [AuthController::class, 'indexUsuarios']);
Route::get('/vehiculos-options', [VehiculoController::class, 'options']);
Route::post('clientes/importar-excel', [ClienteController::class, 'importExcel']);   
Route::apiResource('revision-comparendos',RevisionComparendoController::class);
Route::get('/revision-comparendos/conductor/{id}', [RevisionComparendoController::class, 'porConductor']);
//Crear plantilla de correo
Route::apiResource('plantillas-correo', PlantillaController::class);
Route::post('/plantillas/{id}/enviar', [PlantillaController::class, 'enviar']);
Route::get('/plantillas/{id}/edit', [PlantillaController::class, 'edit']); 
//Rutas Proveedores detalles item
Route::post('/detalles-orden', [OrdenCompraProveedorController::class, 'storeDetalle']);
Route::put('/detalles-orden/{id}', [EntregaProveedorController::class, 'updateDetalle']);
Route::put('/ordenes-compra-proveedor/{id}/update-proveedor', [OrdenCompraProveedorController::class, 'updateProveedor']);
Route::delete('/detalles-orden/{id}', [EntregaProveedorController::class, 'eliminarItem']);
//Entregas proveedor

Route::post('entregas-proveedor', [EntregaProveedorController::class, 'store']);
Route::put('entregas-proveedor/{id}', [EntregaProveedorController::class, 'update']);
Route::get('entregas/proveedores/{id}', [OrdenCompraProveedorController::class, 'entregasShow']);
Route::get('/proveedores-all', [ProveedorController::class, 'proveedoresAll']);
Route::apiResource('ordenes-compra-proveedor', OrdenCompraProveedorController::class);
Route::get('referencias-faltantes', [EntregaProveedorController::class, 'referenciasFaltantes']);
Route::get('referencias-faltantes/{ordenId}', [EntregaProveedorController::class, 'referenciasFaltantesbyId']);


Route::get('/dashboard/ordenes-anuales', [EntregaProveedorController::class, 'dashboardOrdenesAnual']);

//Descargar pendientes de ordenes de proveedor

Route::get('/entregas/items-pendientes/pdf', [EntregaProveedorController::class, 'descargarPendientes']);
//Descargar la orden de compra del proveedor en pdf
Route::get('/orden-compras-proveedor/{id}/pdf', [OrdenCompraProveedorController::class, 'descargarOrdenPdfProveedor']);
//Enviar email con la orden de compra al proveedor
Route::post('/ordenes-compra-proveedor/{id}/enviar-email', [OrdenCompraProveedorController::class, 'enviarEmail']);

//Ordenes de trabajo para entregas
Route::get('ordenes-trabajo-entregas', [OrdenCompraController::class, 'ordenesTrabajoEntregas']);
//Proceso bolsas
Route::apiResource('registrar-proceso-bolsa',procesoBolsasController::class);
Route::get('entregas/{id}', [OrdenCompraController::class, 'obtenerEntregas']);

//Sedes
Route::apiResource('sedes', SedeController::class);



Route::get('audit-ordenes-compra',[DashboardController::class,'getAuditData']);
//Stock con sugerencias de productos
Route::get('stock-products-sugerencias/{id}', [CrmProductController::class, 'stockProductoConSugerencias']);
//REGISTRAR REVICION DE LA ORDEN DE COMPRA
Route::post('ordenes-compra/{id}/marcar-documento-revisado', [OrdenCompraController::class, 'marcarDocumentoRevisado']);

Route::get('stock-products/{id}', [CrmProductController::class, 'stock']);
Route::get('stock-products-for-user/{id}', [CrmProductController::class, 'stockForUserAndOrder']);
//Registrar entrada de stock Manualmente
Route::post('products/register-stock', [CrmProductController::class, 'registrarEntradaStock']);
Route::post('products/descontar/stock', [InventorieController::class, 'descontarStock']);
Route::post('/products/descontar-stock-masivo', [InventorieController::class, 'descontarStockMasivo']);

//Importar productos via exel
Route::post('products/importar-excel', [CrmProductController::class, 'importarInventarioExcel']);
Route::post('descontar-stock-excel', [CrmProductController::class, 'importarExcelDescuento']);
Route::get('products/exportar/plantilla',[CrmProductController::class,'exportarPlantillaProductos']);

//Crear productos via excel
Route::post('products/crear-productos-excel', [CrmProductController::class, 'crearProductosExcel']);

//Exportar inventario a excel
Route::get('inventarios-exportar-exel', [InventorieController::class, 'exportarInventarioExcel']);

//Treaer Movimientos de  stock en   pdf
Route::get('movimientos-stock/{id}/pdf', [CrmProductController::class, 'getMovimientoPDF']);
//TRASLADOS INTERNOS
Route::apiResource('traslados-internos', EnvioInternoController::class);
  
Route::get('traslados-internos-sedes', [EnvioInternoController::class, 'traerSedes']);
Route::get('traslados-internos-ordenes-compra', [EnvioInternoController::class, 'traerOrdenesCompra']);
Route::get('/oc-traslados/{id}', [EnvioInternoController::class, 'mostrarOC']);
Route::get('ordenes-compra-pendientes', [EnvioInternoController::class, 'traerOrdenesCompraPendientes']);
Route::post('/productos/sincronizar-siigo', [CrmProductController::class, 'sincronizarProductosSiigoGlobal']);
Route::post('/productos/sincronizar-siigo-setas', [CrmProductController::class, 'sincronizarProductosSiigoSetas']);

 Route::apiResource('bodegas', BodegaController::class);
 Route::get('bodegas-all', [BodegaController::class, 'getAllBodegas']);

//**LOGICA DE INVENTARIOS */

//** RUTAS API PRODUCTOS */
Route::apiResource('products',CrmProductController::class);
//**ACTUALIZAR PRODUCTO */
Route::put('productos/{id}', [CrmProductController::class, 'edit']);

Route::apiResource('inventarios',InventorieController::class);
//Anular movimiento de stock
Route::post('anular-movimiento-stock/{movimientoId}', [InventorieController::class, 'importar']);
//Consultar movimientos de stock
Route::get('movimientos-stock', [InventorieController::class, 'listarMovimientosStock']);

/*DESCONTAR STOCK VIA EXCEL*/



//Registrar Evento de entrega
Route::apiResource('/eventos-entrega', DeliveryEventController::class);
Route::get('/eventos-entrega-por-usuario', [DeliveryEventController::class, 'listarEntregasPorUsuario']);
//Cambio de estado de la entrega
Route::post('/eventos-entrega/{deliveryEvent}/change-status', [DeliveryEventController::class, 'changeStatus']);

Route::apiResource('procesos', ProcesoController::class);

//Guardar Rutas y permisos
Route::post('/guardar-rutas', [PermissionController::class, 'guardarRutas']);
Route::post('/roles/asignar-permisos', [PermissionController::class, 'asignar']);
Route::get('/roles/{role_id}/permisos', [PermissionController::class, 'obtenerPorRol']);
//CCargar Rutas
Route::get('permissions', [PermissionController::class, 'index']);
Route::get('user-permissions', [PermissionController::class, 'permissions']);

Route::get('roles-permissions', [PermissionController::class, 'permissions']);
//Asignar rol por usuario
Route::post('asignar-permisos-usuario', [PermissionController::class, 'asignarPermisosUsuario']);
Route::get('/user-permissions/{user_id}', [PermissionController::class, 'permisosDeUsuario']);

//ALISTAMIENTOS VSM
Route::post('/vsm-alistamientos', [AlistamientoController::class, 'store']);

Route::post('/vsm-alistamientos/{id}/pausar', [AlistamientoController::class, 'pausar']);
Route::post('/vsm-alistamientos/{id}/reanudar', [AlistamientoController::class, 'reanudar']);
Route::post('/vsm-alistamientos/{id}/finalizar', [AlistamientoController::class, 'finalizar']);
Route::get('alistamientos/{id}/historial', [AlistamientoController::class, 'historial']);

//Obtener alistamientos activos
Route::get('alistamientos-activos', [AlistamientoController::class, 'alistamientosActivos']);

Route::post('/alistamientos/{alistamiento_id}/usuarios/{usuario_id}/pausar', [AlistamientoController::class, 'pausarUsuario']);
Route::post('/alistamientos/{alistamiento_id}/usuarios/{usuario_id}/reanudar', [AlistamientoController::class, 'reanudarUsuario']);
//Traer ordenes de trabajo para alistamiento
Route::get('ordenes-trabajo-alistamiento', [AlistamientoController::class, 'ordenesTrabajoAlistamiento']);
//Tiempos por alistamiento

Route::get('/vsm/pronostico', [ForecastController::class, 'pronostico']);
Route::get('/vsm/pronostico/{id}', [ForecastController::class, 'pronosticoOT']);

Route::get('/vsm/flujo', [ForecastController::class, 'flujo']);

//Traer ot finalizadas
Route::get('/vsm/ots-finalizadas', [AlistamientoController::class, 'alistamientosFinalizados']);
//Usuarios disponibles para alistamiento
Route::get('/alistamientos/{alistamiento_id}/usuarios-disponibles', [AlistamientoController::class, 'usuariosDisponibles']);
//Agregar usuario al alistamiento
Route::post('/alistamientos/{alistamiento_id}/agregar-usuario', [AlistamientoController::class, 'agregarUsuario']);

//**RUTAS PARA GESTIONAR RESPONSABILIDADES */

Route::apiResource('responsabilidades', ResponsabilidadesController::class); 
Route::post('responsabilidades/{id}/asignar', [ResponsabilidadesController::class, 'asignarResponsabilidad']);
Route::put('responsabilidades/{pivotId}/update', [ResponsabilidadesController::class, 'actualizarAsignacion']);
Route::delete('responsabilidades/{pivotId}/remover', [ResponsabilidadesController::class, 'desactivarAsignacion']);
Route::get('responsabilidades-asignadas', [ResponsabilidadesController::class, 'mostrarResponsabilidadesAsignadas']);
Route::apiResource('traslados-bodegas',TrasladosBodegaController::class);

//Aprobaciones
Route::post('traslados-bodegas/{id}/aprobar', [TrasladosBodegaController::class, 'aprobarPorBodega']);
Route::post('traslados-bodegas/{id}/rechazar', [TrasladosBodegaController::class, 'rechazarPorBodega']);
Route::put('traslados-bodegas/{id}', [TrasladosBodegaController::class, 'update']);
//Aprobacion por inventario
Route::post('traslados-bodegas/{id}/aprobar-inventario', [TrasladosBodegaController::class, 'aprobarInventario']);
//DASHBOARD INDICADORESº
    Route::get('/rendimiento-indicadores', [RegistroIndicadoresController::class, 'indexByCompany']);
//RUTAS PARA REGISTRO DIARIO DE PREGUNTAS SEGUIMIENTO
Route::apiResource('registro-diario', RegistroDiarioController::class);
Route::apiResource('preguntas', PreguntaController::class);
Route::apiResource('verificacion', VerificacionDiariaControllerr::class);
Route::get('/estadisticas-anuales-verificacion/{anio}', [VerificacionDiariaControllerr::class, 'index']);
Route::get('/estadisticas-anuales-departamentos/{anio}', [RegistroDiarioController::class, 'index']);

//RUTAS  PARA NOVEDADES PRODUCTOS  NO CONFORMES
Route::apiResource('novedades', NovedadController::class);


//RUTAS DE ASGINACION DE EQUIPOS TIC
Route::apiResource('asignaciones', AsignacionesController::class);

Route::get('/productos-asignar', [CrmProductController::class, 'productQuery']);

Route::apiResource('categorias',CategoriaController::class);


//MANTENIMIENTO DE EQUIPOS TIC
Route::apiResource('mantenimiento-equipos-tic', MantenimientoEquiposController::class);
Route::post('ejecutar-mantenimiento-equipos-tic/{id}', [MantenimientoEquiposController::class, 'update']);
Route::post('mantenimiento-equipos-tic/{id}/actualizar', [MantenimientoEquiposController::class, 'actualizarMantenimiento']);
Route::get('tic-estadisticas-mensuales', [MantenimientoEquiposController::class, 'estadisticasMensuales']);


Route::apiResource('categorias',CategoriaController::class);

Route::get('obtener-mantenimientos-tic', [MantenimientoEquiposController::class, 'obtenerMantenimientos']);
Route::put('mantenimiento-equipos-tic/{id}/actualizar-estado', [MantenimientoEquiposController::class, 'actualizarEstado']);

//RUTAS PARA ACTUALIZAR DEPARTAMENTOSRUTAS PARA ORDENES DE SERVICIO
Route::apiResource('ordenes-servicio', OrdenesServicioController::class);
Route::get('ordenes-servicio/{id}/show', [OrdenesServicioController::class, 'obtenerOrdenesShow']);



//RUTAS PARA GESTIONAR CARTERA
Route::apiResource('gestion-cartera', GestionCarteraController::class);
Route::apiResource('abonos-cartera',GestionPivoteCarteraController::class);
Route::get('estadisticas-cartera', [GestionCarteraController::class, 'estadisticasCartera']);
Route::get('recaudo-semanal', [GestionCarteraController::class, 'recaudoSemanal']);
//RUTAS PARA HSEQ
Route::apiResource('tipo-servicios', TipoServicioController::class);
Route::get('estadisticas-anuales-consumo', [ConsumoServicioController::class, 'estadisticasAnuales']);
Route::apiResource('consumo-servicios', ConsumoServicioController::class);
//RUTAS PARA RESIDUOS
Route::apiResource('tipo-residuos', TipoResiduoController::class);
Route::get('estadisticas-anuales-residuos', [ResiduoController::class, 'estadisticasAnuales']);
Route::apiResource('generacion-residuos', ResiduoController::class);

//RUTAS PARA TIPO DE INSPECCION
Route::apiResource('tipo-inspecciones', TipoInspeccionController::class);
Route::apiResource('preguntas-inspecciones', PreguntaInspeccionController::class);
Route::get('preguntas-tipo-inspecciones', [PreguntaInspeccionController::class, 'preguntasPorTipoInspeccion']);
Route::apiResource('inspecciones-hseq', InspeccionHseqController::class);
Route::apiResource('respuestas-inspecciones', RespuestaInspeccionController::class);
Route::get('indicador-semestral', [HallazgoNovedadController::class, 'indicadorSemestral']);

Route::apiResource('hallazgo-inspecciones', HallazgoNovedadController::class);
Route::apiResource('hseq-dashboard', HseqDashboardController::class);
Route::apiResource('hallazgos', HallazgoNovedadController::class);

});

//**RUTAS MIDDLEWARE PARA RESPONSABLES DE CADA PROCESOS O DEPARTAMENTO */


Route::middleware(['auth:sanctum', 'es_responsable_del_departamento'])->group(function () {
    Route::apiResource('/indicadores', IndicadoresProcesosController::class);
    Route::apiResource('registro-indicadores',RegistroIndicadoresController::class);

   Route::get ('/indicadoresAdmin',[IndicadoresProcesosController::class,'indexAdmin']);
   //Inventarios
   
    //Categorias

    //Productos
    
});


//**RUTAS PARA GESTIONAR EMPRESAS SOLO ROLE 1   ADMINISTRADOR DEL SISTEMA */
Route::middleware(['auth:sanctum', 'role:1'])->group(function () {
  Route::apiResource('empresas', EmpresaController::class);

});


//**RUTAS PARA GESTIONAR ROLES  */
Route::middleware(['auth:sanctum', 'check.permission'])->group(function () {


});

//**RUTAS SIN AUTENTICACION */


Route::get('empresas-all', [EmpresaController::class, 'index']);
Route::get('empresas-all', [EmpresaController::class, 'index']);
//Ruta para descargar archivos de el registro de indicadores
Route::get('/registro-indicadores/descargar/{id}', [RegistroIndicadoresController::class, 'descargarDocumento']);
Route::apiResource('departamentos', DepartamentoController::class);
Route::put('/documentos/{id}/fechas', [DocumentoVehiculoController::class, 'actualizarFechas']);
Route::get('/conductores',[AuthController::class, 'userAll']);


Route::post('pqr', [PqrController::class, 'store']);

Route::get('/sedes', [SedeController::class, 'index']);


Route::delete('orden-compras/{id}', [OrdenCompraController::class, 'destroy']);
Route::get('estadisticas-comerciales',[SeguimientoController::class, 'resumenMensualPorUsuario']);
Route::put('/pqrs/{id}/asignar', [PqrController::class, 'asignarArea']);


//Dashboard y reportes principal
Route::get('top-clients', [DashboardController::class, 'getTopClients']);
Route::get('dashboard/monthly', [DashboardController::class, 'getMonthlyStats']);
Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);
Route::get('/orden-compras/{id}/pdf', [OrdenCompraController::class, 'generarPdf']);
Route::get('/cotizaciones/{id}/pdf', [CotizacionController::class, 'descargarPDF']);
Route::get("/usuarios-comerciales",[ClienteController::class,'usuariosComerciales']);
Route::get('dashboard-comercial-mes-a-mes', [SeguimientoController::class, 'dashboardComercialMesAMes']);
Route::get('/dashboard/ordenespdf', [DashboardController::class, 'descargarOrdenesCriticasHoy']);

Route::get('dashboard-entregas-hoy', [DashboardController::class, 'ordenesEntreganHoy']);


Route::get('documentos/descargar/{id}', [DocumentoController::class, 'download']);

Route::apiResource('documentos', DocumentoController::class);


Route::apiResource('errores', ErrorController::class);
Route::apiResource('roles', RolController::class);
Route::put('update/{id}', [UsuarioController::class, 'update']);

Route::post('contacto', [PqrController::class, 'contacto']);
//rutas crm
Route::get('/seguimientos', [SeguimientoController::class, 'index']);


Route::get('procesos/departamento/{departamento_id}', [ProcesoController::class, 'index']);

Route::get('download/{id}', [DocumentosAdministrativosController::class, 'downloand']);
Route::delete('documentos-administrativos/{id}', [DocumentosAdministrativosController::class, 'destroy']);
Route::apiResource('carpetas', CarpetaController::class);
Route::post('login', [AuthController::class, 'login'])->name('login');
//Whatsapp Webhook
Route::match(['GET', 'POST'], '/webhook', [WhatsappWebhookController::class, 'handle']);

//Descargar órdenes críticas de hoy en PDF

//Consultar todos los productos sin paginar
Route::get('products-all', [CrmProductController::class, 'getAllProducts']);

Route::post('productos', [CrmProductController::class, 'createProduct']);

//Generar códigos de barra para productos
Route::post('/products/generar-barcodes', [CrmProductController::class, 'barcodesMasivos']);
//Registrar meta mensual
Route::post('/meta-mensual', [OrdenCompraController::class, 'registrarMeta']);
//Resumen de meta mensual
Route::get('/meta-mensual/resumen', [OrdenCompraController::class, 'graficoMetaMensual']);
Route::get('/dashboard/exportar-ordenes-criticas-mes', [DashboardController::class, 'exportarOrdenesCriticasMes']);
//Rutas para el módulo de eventos
Route::apiResource('/eventos', EventoController::class);
Route::post('/eventos/crear-qr', [EventoController::class, 'crearQr']);
Route::post('/qrs/productos/pdf', [EventoController::class, 'crearQrMasivoConPdf']);

Route::get('/orden-compras/{orden}/preview-documento', [OrdenCompraController::class, 'previewDocumento']);