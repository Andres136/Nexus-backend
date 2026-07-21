<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CapacitacionController;
use App\Http\Controllers\CapacitacionEncuestaController;
use App\Http\Controllers\comunicaciones\PlantillaController;
use App\Http\Controllers\comunicaciones\TicketController;
use App\Http\Controllers\Compras\RequerimientoCompraController;
use App\Http\Controllers\contabilidad\CosteoController;
use App\Http\Controllers\contabilidad\FacturaCompraController;
use App\Http\Controllers\contabilidad\FormaPagoController;
use App\Http\Controllers\contabilidad\ImpuestoController;
use App\Http\Controllers\contabilidad\PuckController;
use App\Http\Controllers\contabilidad\RegistroPagoFacturaCompraController;
use App\Http\Controllers\Corporate\CorporateDocumentController;
use App\Http\Controllers\Crm\AlistamientoOtController;
use App\Http\Controllers\Crm\BodegaController;
use App\Http\Controllers\Crm\CarpetaController;
use App\Http\Controllers\Crm\CategoriaController;
use App\Http\Controllers\Crm\ClienteController;
use App\Http\Controllers\Crm\EncuestaController;
use App\Http\Controllers\Crm\CotizacionController;
use App\Http\Controllers\Crm\DashboardController;
use App\Http\Controllers\Crm\DatoCondutorController;
use App\Http\Controllers\Crm\DocumentosAdministrativosController;
use App\Http\Controllers\Crm\DocumentoVehiculoController;
use App\Http\Controllers\Crm\EmpresaController;
use App\Http\Controllers\Crm\EntregaProveedorController;
use App\Http\Controllers\Crm\EventoController;
use App\Http\Controllers\Crm\GestionCarteraController;
use App\Http\Controllers\Crm\GestionCarteraHistorialController;
use App\Http\Controllers\Crm\GestionPivoteCarteraController;
use App\Http\Controllers\Crm\InspeccionController;
use App\Http\Controllers\Crm\InventorieController;
use App\Http\Controllers\Crm\MantenimientoController;
use App\Http\Controllers\Crm\Orden_servicio\OrdenesServicioController;
use App\Http\Controllers\Crm\OrdenCompraController;
use App\Http\Controllers\Crm\OrdenCompraDetallesController;
use App\Http\Controllers\Crm\OrdenCompraProveedorController;
use App\Http\Controllers\Crm\OrdenComprasHistorialController;
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
use App\Http\Controllers\DashboardOperativoController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\Hseq\ConsumoServicioController;
use App\Http\Controllers\Hseq\AnalisisProductoNoConformeController;
use App\Http\Controllers\Hseq\ProductoNoConformeController;
use App\Http\Controllers\Hseq\HallazgoNovedadController;
use App\Http\Controllers\Hseq\HallazgoSeguimientoController;
use App\Http\Controllers\Hseq\HseqDashboardController;
use App\Http\Controllers\Hseq\InspeccionHseqController;
use App\Http\Controllers\Hseq\PreguntaInspeccionController;
use App\Http\Controllers\Hseq\ReporteBicConttroller;
use App\Http\Controllers\Hseq\ResiduoController;
use App\Http\Controllers\Hseq\RespuestaInspeccionController;
use App\Http\Controllers\Hseq\SoporteTareaController;
use App\Http\Controllers\Hseq\TipoInspeccionController;
use App\Http\Controllers\Hseq\TipoResiduoController;
use App\Http\Controllers\Hseq\TipoServicioController;
use App\Http\Controllers\IndicadoresProcesosController;
use App\Http\Controllers\MacroProcesoController;
use App\Http\Controllers\Nomina\ContratacionController;
use App\Http\Controllers\Nomina\ContratacionCambioController;
use App\Http\Controllers\Nomina\ConfiguracionNominaController;
use App\Http\Controllers\Nomina\ComisionController;
use App\Http\Controllers\Nomina\DescuentoController;
use App\Http\Controllers\Nomina\HorarioOperacionDiariaController;
use App\Http\Controllers\Nomina\HorarioUsuarioSemanalController;
use App\Http\Controllers\Nomina\IncapacidadController;
use App\Http\Controllers\Nomina\JornadaLaboralController;
use App\Http\Controllers\Nomina\SeguridadSocialController;
use App\Http\Controllers\Nomina\TipoContratoController;
use App\Http\Controllers\Nomina\HorarioLaboralController;
use App\Http\Controllers\Nomina\TransacionalRegistroController;
use App\Http\Controllers\Nomina\KioskoDeviceController;
use App\Http\Controllers\Nomina\NominaController;
use App\Http\Controllers\Nomina\AjusteSalarialContratacionController;
use App\Http\Controllers\Nomina\TipoRegistroController;
use App\Http\Controllers\Nomina\UsersFacePhotoController;
use App\Http\Controllers\Nomina\ValorController;
use App\Http\Controllers\Nomina\WorkSessionController;
use App\Http\Controllers\Nomina\HoraExtraController;
use App\Http\Controllers\Nomina\RecuperacionTiempoController;
use App\Http\Controllers\Nomina\PermisoController;
use App\Http\Controllers\Nomina\VacacionController;
use App\Http\Controllers\Nomina\LlamadoAtencionController;
use App\Http\Controllers\Nomina\DescargoController;
use App\Http\Controllers\Nomina\LicenciaController;
use App\Http\Controllers\Nomina\LiquidacionPrestacionController;
use App\Http\Controllers\Nomina\LiquidacionRetiroController;
use App\Http\Controllers\Nomina\NovedadRetroactivaController;
use App\Http\Controllers\Nomina\NominaConceptoContableController;
use App\Http\Controllers\Nomina\NominaParametroLaboralController;
use App\Http\Controllers\Nomina\PortalEmpleadoController;
use App\Http\Controllers\Nomina\SolicitudPrestamoController;
use App\Http\Controllers\NotificacionOrdenController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PqrController;
use App\Http\Controllers\Productividad\AdminProductividadController;
use App\Http\Controllers\Productividad\MiDiaController;
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
use App\Http\Controllers\Vsm\VsmConfiguracionController;
use App\Http\Controllers\Vsm\AlistamientoTiempoController;
use App\Http\Controllers\Vsm\ForecastController;
use App\Http\Controllers\whatsapp\WhatsappWebhookController;
use App\Models\Pqr;
use App\Models\Registro_indicadores;
use App\RolEnum;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

// Rutas públicas encuestas (sin auth)
Route::get('/r/{token}',  [EncuestaController::class, 'showPublico']);
Route::post('/r/{token}', [EncuestaController::class, 'responder']);
Route::get('capacitacion-encuestas/publica/{token}', [CapacitacionEncuestaController::class, 'showPublica']);
Route::get('corporate-documents', [CorporateDocumentController::class, 'index']);
Route::get('corporate-documents/{slug}/download', [CorporateDocumentController::class, 'downloadFile']);
Route::post('corporate-documents/{slug}/download', [CorporateDocumentController::class, 'download']);
Route::get('corporate-documents/{slug}', [CorporateDocumentController::class, 'show']);
Route::post('capacitacion-encuestas/publica/{token}', [CapacitacionEncuestaController::class, 'responderPublica']);
Route::post('nomina/kiosko-devices/activate', [KioskoDeviceController::class, 'activateDevice']);
Route::post('nomina/kiosko-devices/validate-session', [KioskoDeviceController::class, 'validateSession']);
Route::post('nomina/kiosko-devices/bootstrap', [KioskoDeviceController::class, 'bootstrap']);
Route::post('nomina/kiosko-devices/bootstrap-guest', [KioskoDeviceController::class, 'bootstrapGuest']);
Route::get('nomina/kiosko-horario-operacion/hoy', [HorarioOperacionDiariaController::class, 'kioskShow']);
Route::get('nomina/kiosko-horario-usuario/hoy', [HorarioUsuarioSemanalController::class, 'kioskHoy']);
Route::get('nomina/kiosko-face-photos/{uuid}/image', [UsersFacePhotoController::class, 'kioskImage']);
Route::get('nomina/kiosko-work-sessions', [WorkSessionController::class, 'kioskIndex']);
Route::post('nomina/kiosko-work-sessions', [WorkSessionController::class, 'kioskStore']);
Route::put('nomina/kiosko-work-sessions/{uuid}', [WorkSessionController::class, 'kioskUpdate']);
Route::get('nomina/kiosko-permisos', [PermisoController::class, 'kioskIndex']);
Route::get('nomina/kiosko-horas-extras', [HoraExtraController::class, 'kioskIndex']);

Route::middleware('auth:sanctum')->group(function () {
  // RUTAS PARA MI DIA (productividad personal) — disponibles para cualquier usuario autenticado
  Route::get('mi-dia', [MiDiaController::class, 'index']);
  Route::get('mi-dia/linea-tiempo', [MiDiaController::class, 'lineaTiempo']);
  Route::get('mi-dia/categorias', [MiDiaController::class, 'categorias']);
  Route::get('mi-dia/sugerencias-tarea', [MiDiaController::class, 'sugerenciasTarea']);
  Route::post('mi-dia/actividades/iniciar', [MiDiaController::class, 'iniciar']);
  Route::post('mi-dia/disponible', [MiDiaController::class, 'disponible']);
  Route::post('mi-dia/actividades/{uuid}/completar', [MiDiaController::class, 'completar']);
  Route::post('mi-dia/actividades/{uuid}/bloquear', [MiDiaController::class, 'bloquear']);
  Route::post('mi-dia/actividades/{uuid}/cancelar', [MiDiaController::class, 'cancelar']);
  Route::post('mi-dia/actividades/{uuid}/reanudar', [MiDiaController::class, 'reanudar']);

  // Panel administrativo de productividad — solo rol Administrador
  Route::middleware('role:' . RolEnum::ADMINISTRADOR->value)
    ->prefix('admin/productividad')
    ->group(function () {
      Route::get('equipo', [AdminProductividadController::class, 'equipo']);
      Route::get('equipo/exportar', [AdminProductividadController::class, 'exportar']);
      Route::get('usuarios/{id}', [AdminProductividadController::class, 'usuario'])->whereNumber('id');
      Route::post('actividades/{uuid}/corregir', [AdminProductividadController::class, 'corregir']);
    });

  Route::get('admin/corporate-documents', [CorporateDocumentController::class, 'adminIndex']);
  Route::post('admin/corporate-documents', [CorporateDocumentController::class, 'adminStore']);
  Route::get('admin/corporate-documents/{corporateDocument}', [CorporateDocumentController::class, 'adminShow']);
  Route::put('admin/corporate-documents/{corporateDocument}', [CorporateDocumentController::class, 'adminUpdate']);
  Route::delete('admin/corporate-documents/{corporateDocument}', [CorporateDocumentController::class, 'adminDestroy']);

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
  Route::get('requerimientos-compra/bodegas-disponibles', [RequerimientoCompraController::class, 'bodegasDisponibles']);
  Route::get('requerimientos-compra/{uuid}/pdf', [RequerimientoCompraController::class, 'pdf']);
  Route::patch('requerimientos-compra/{uuid}/analizar', [RequerimientoCompraController::class, 'analizar']);
  Route::put('requerimientos-compra/{uuid}', [RequerimientoCompraController::class, 'update']);
  Route::patch('requerimientos-compra/{uuid}', [RequerimientoCompraController::class, 'update']);
  Route::patch('requerimientos-compra/{uuid}/aprobar', [RequerimientoCompraController::class, 'aprobar']);
  Route::patch('requerimientos-compra/{uuid}/rechazar', [RequerimientoCompraController::class, 'rechazar']);
  Route::patch('requerimientos-compra/{uuid}/cancelar', [RequerimientoCompraController::class, 'cancelar']);
  Route::post('requerimientos-compra/{uuid}/generar-orden-compra', [RequerimientoCompraController::class, 'generarOrdenCompra']);
  Route::apiResource('requerimientos-compra', RequerimientoCompraController::class)->only(['index', 'store', 'show']);
  Route::get('/notificaciones', [NotificacionOrdenController::class, 'listarNotificaciones']);
  Route::post('/notificaciones/{id}/marcar-leida', [NotificacionOrdenController::class, 'marcarComoLeida']);
 Route::get('ordenes-compra-facturar', [OrdenCompraController::class, 'ordenesFacturar']);

  Route::post('/orden-trabajo/{id}', [OrdenCompraController::class, 'generarOrdenTrabajo']);
  Route::get('tareas-vencidas', [NotificacionOrdenController::class, 'EnviarTaskVencida']);
  Route::apiResource('macroprocesos', MacroProcesoController::class);
  Route::apiResource('estados', EstadoController::class);
  Route::get('usuarios/departamento/{departamento_id}', [AuthController::class, 'DepartamentoUsuario']);
  Route::get('/documentacion/{id}', [DocumentoController::class, 'index']);
  Route::get('/errores/kpi', [ErrorController::class, 'kpiErrores']);
  Route::apiResource('clientes/{cliente}/seguimientos', SeguimientoController::class);
  Route::patch('clientes/{id}/estado', [ClienteController::class, 'cambiarEstado']);

  // Encuestas
  Route::apiResource('encuestas', EncuestaController::class);
  Route::post('encuestas/{id}/enviar', [EncuestaController::class, 'enviar']);
  Route::get('encuestas/{id}/resultados', [EncuestaController::class, 'resultados']);
  Route::get('encuestas-clientes', [EncuestaController::class, 'clientesParaEncuesta']);
  Route::get('encuestas-indice-general', [EncuestaController::class, 'indiceGeneral']);

  // Capacitaciones
  Route::apiResource('capacitaciones', CapacitacionController::class);
  Route::get('capacitacion-encuestas-usuarios', [CapacitacionEncuestaController::class, 'usuarios']);
  Route::get('capacitacion-encuestas/{uuid}/resultados', [CapacitacionEncuestaController::class, 'resultados']);
  Route::post('capacitacion-encuestas/{uuid}/enviar', [CapacitacionEncuestaController::class, 'enviar']);
  Route::apiResource('capacitacion-encuestas', CapacitacionEncuestaController::class);
  

  
 
  Route::apiResource('registrar-documentacion', DocumentosAdministrativosController::class);
  Route::post('/documentos/mover-obseletos/{id}', [DocumentoController::class, 'moverAObseletos']);


//Consumir api siigo
  Route::get('products-setas', [SiigoController::class, 'index']);
 Route::get('stock', [SiigoController::class, 'stock']);
   //Consumir api siigo global
  Route::get('products-global', [SiigoGlobalController::class, 'index']);
  Route::get('stock-global', [SiigoGlobalController::class, 'stock']);
  //Siigo facturas de compra

  Route::patch('tareas/estado/{id}/', [TareaController::class, 'update']);
  Route::apiResource('tareas', TareaController::class);
  Route::put('/tareas/update/{id}', [TareaController::class, 'actualizarTarea']);
  Route::post('/tareas/{id}/notas', [TareaController::class, 'agregarNota']);
  Route::get('/tareas/{id}/historial', [TareaController::class, 'historial']);
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
Route::apiResource('ordenes-compras-historial', OrdenComprasHistorialController::class);
Route::get('/ordenes-compra/faltantes/pendientes', [OrdenCompraController::class, 'verificarFaltantesPendientes']);
Route::get('/ordenes-compra/faltantes/estadisticas', [OrdenCompraController::class, 'estadisticasFaltantes']);
//KPIS DASHBOARD CLIENTES
Route::get('/dashboard/kpis', [DashboardController::class, 'kpis']);
Route::get('estadisticas-comerciales',[SeguimientoController::class, 'resumenMensualPorUsuario']);
Route::get('estadisticas-comerciales/semanas', [SeguimientoController::class, 'resumenSemanalDelMes']);
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
//Revisar orden de trabajo
Route::post('/orden-trabajo/{id}/revisar', [ordenTrabajoController::class, 'revisarOrdenTrabajo']);

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
Route::get('tickets/estadisticas-paradas', [TicketController::class, 'estadisticasParadas']);
Route::get('tickets/estadisticas-generales', [TicketController::class, 'estadisticasGenerales']);
Route::get('tickets/resumen-asignados', [TicketController::class, 'resumenAsignados']);
Route::patch('tickets/{ticket}/estado', [TicketController::class, 'cambiarEstado']);
Route::post('tickets/{ticket}/historial', [TicketController::class, 'agregarHistorial']);
Route::apiResource('tickets', TicketController::class);
//Rutas Proveedores detalles item
Route::post('/detalles-orden', [OrdenCompraProveedorController::class, 'storeDetalle']);
Route::post('/detalles-orden/prioridad-existente', [OrdenCompraProveedorController::class, 'storePrioridadDetalleExistente']);
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
Route::post('/alistamiento/produccion', [AlistamientoController::class, 'registrarProduccion']);
//Obtener alistamientos activos
Route::get('alistamientos-activos', [AlistamientoController::class, 'alistamientosActivos']);
Route::post('pausar-ordenes-sedes', [AlistamientoController::class, 'pausarPorSede']);
Route::post('reanudar-ordenes-sedes', [AlistamientoController::class, 'reanudarPorSede']);

Route::post('/alistamientos/{alistamiento_id}/usuarios/{usuario_id}/pausar', [AlistamientoController::class, 'pausarUsuario']);
Route::post('/alistamientos/{alistamiento_id}/usuarios/{usuario_id}/reanudar', [AlistamientoController::class, 'reanudarUsuario']);
Route::delete('/alistamientos/{alistamiento_id}/usuarios/{usuario_id}', [AlistamientoController::class, 'eliminarUsuario']);
//Traer ordenes de trabajo para alistamiento
Route::get('ordenes-trabajo-alistamiento', [AlistamientoController::class, 'ordenesTrabajoAlistamiento']);
//Tiempos por alistamiento
Route::get('/kpi-productividad',   [ForecastController::class, 'kpiProductividad']);
Route::get('/vsm/rendimiento',     [ForecastController::class, 'rendimientoPorPeriodo']);
Route::get('/vsm/pronostico', [ForecastController::class, 'pronostico']);
Route::get('/vsm/pronostico/{id}', [ForecastController::class, 'pronosticoOT']);


Route::get('/vsm/flujo', [ForecastController::class, 'flujo']);
Route::get('/vsm/cobertura-abastecimiento', [ForecastController::class, 'coberturaAbastecimiento']);
Route::get('/vsm/capacidad', [ForecastController::class, 'capacidad']);

// Configuración de meta de productividad VSM
Route::get('/vsm/configuracion',                         [VsmConfiguracionController::class, 'vigente']);
Route::get('/vsm/configuracion/historial',               [VsmConfiguracionController::class, 'historial']);
Route::post('/vsm/configuracion',                        [VsmConfiguracionController::class, 'store']);
Route::put('/vsm/configuracion/{id}',                    [VsmConfiguracionController::class, 'update']);
Route::delete('/vsm/configuracion/{id}',                 [VsmConfiguracionController::class, 'destroy']);
Route::post('/vsm/configuracion/{id}/restaurar',         [VsmConfiguracionController::class, 'restaurar']);

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
Route::get('asignaciones/usuario/{userId}', [AsignacionesController::class, 'byUsuario']);
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
Route::get('gestion-cartera/exportar', [GestionCarteraController::class, 'exportar']);
Route::apiResource('gestion-cartera', GestionCarteraController::class);
Route::apiResource('abonos-cartera',GestionPivoteCarteraController::class);
Route::get('estadisticas-cartera', [GestionCarteraController::class, 'estadisticasCartera']);
Route::get('recaudo-semanal', [GestionCarteraController::class, 'recaudoSemanal']);
Route::apiResource('gestion-facturas-cartera', GestionCarteraHistorialController::class);
Route::delete('gestion-cartera-destroy/{id}', [GestionCarteraController::class, 'anularFactura']);
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
Route::get('hseq-descargar-hallazgos-pdf', [HseqDashboardController::class, 'descargarHallazgosPdf']);
Route::get('hseq-dashboard/inspecciones/finalizadas', [HseqDashboardController::class, 'inspeccionesFinalizadas']);
Route::apiResource('hallazgos', HallazgoNovedadController::class);
Route::apiResource('seguimiento-hallazgos', HallazgoSeguimientoController::class);
Route::apiResource('soporte-tareas', SoporteTareaController::class);
Route::get('soporte-tarea/{tarea_id}', [SoporteTareaController::class, 'getByTareaId']);
Route::get('soporte-tareas/hallazgo/{soporte_id}', [SoporteTareaController::class, 'getByHallazgoId']);

//RUTAS PARA PRODUCTOS NO CONFORMES
//  Registro y listado ("mis no conformidades"): cualquier usuario autenticado.
Route::apiResource('productos-no-conformes', ProductoNoConformeController::class)->only(['index', 'store']);

//  Dashboard de estadísticas y Gestión (análisis/tratamiento/cierre): solo responsables de departamento o administrador.
Route::middleware('es_responsable_del_departamento')->group(function () {
    Route::get('productos-no-conformes/estadisticas', [ProductoNoConformeController::class, 'estadisticas']);
    Route::apiResource('productos-no-conformes', ProductoNoConformeController::class)->only(['show', 'update', 'destroy']);
    Route::patch('productos-no-conformes/{id}/estado', [ProductoNoConformeController::class, 'cambiarEstado']);

    //RUTAS PARA ANÁLISIS DE PRODUCTOS NO CONFORMES
    Route::post('analisis-producto-no-conforme', [AnalisisProductoNoConformeController::class, 'store']);
    Route::get('analisis-producto-no-conforme/{id}', [AnalisisProductoNoConformeController::class, 'show']);
    Route::get('analisis-producto-no-conforme/producto/{productoNoConformeId}', [AnalisisProductoNoConformeController::class, 'showByProducto']);
    Route::put('analisis-producto-no-conforme/{id}', [AnalisisProductoNoConformeController::class, 'update']);
    Route::patch('analisis-producto-no-conforme/{id}/estado', [AnalisisProductoNoConformeController::class, 'cambiarEstado']);
});

Route::get('/vsm/prioridades', [DashboardOperativoController::class, 'getPrioridades']);
Route::patch('/vsm/origenes/{id}/prioridad', [DashboardOperativoController::class, 'updatePrioridad']);
Route::patch('/vsm/orden-compra-detalles/{id}/observacion', [DashboardOperativoController::class, 'updateObservacionItem']);
Route::get('/vsm/ordenes-pdf', [DashboardOperativoController::class, 'exportarPdf']);
Route::apiResource('/vsm/ordenes', DashboardOperativoController::class);

//RUTAS  PARA CONTABILIDAD
Route::delete(
    'facturas-compra/{id}/eliminar-definitivamente',
    [FacturaCompraController::class, 'eliminarDefinitivamente']
)->middleware('role:' . RolEnum::ADMINISTRADOR->value);
Route::apiResource('facturas-compra', FacturaCompraController::class);
//RUTAS PARA IMPUESTOS
Route::apiResource('impuestos', ImpuestoController::class);
//RUTAS FORMAS DE PAGO
Route::apiResource('formas-pago', FormaPagoController::class);
//RUTAS PUCk
Route::post('cuentas-contables/importar', [PuckController::class, 'import']);
Route::apiResource('cuentas-contables', PuckController::class);
Route::get(
    '/costeos/export',
    [CosteoController::class, 'export']
);
Route::get('/costeos', [CosteoController::class, 'index']);
Route::apiResource('pago-factura-compra', RegistroPagoFacturaCompraController::class);
Route::post(
    'facturas-compras/{facturaId}/pagos',
    [RegistroPagoFacturaCompraController::class, 'store']
);


// Portal del Empleado — siempre escopa al usuario autenticado, sin distinción de roles
Route::prefix('nomina/portal')->group(function () {
    Route::get('nominas',              [PortalEmpleadoController::class, 'nominas']);
    Route::get('certificado',          [PortalEmpleadoController::class, 'certificado']);
    Route::post('certificado/enviar',  [PortalEmpleadoController::class, 'enviarCertificado']);
    Route::get('vacaciones',           [PortalEmpleadoController::class, 'vacaciones']);
    Route::get('vacaciones/resumen',   [PortalEmpleadoController::class, 'resumenVacaciones']);
    Route::post('vacaciones',          [PortalEmpleadoController::class, 'solicitarVacaciones']);
    Route::get('permisos',             [PortalEmpleadoController::class, 'permisos']);
    Route::post('permisos',            [PortalEmpleadoController::class, 'solicitarPermiso']);
    Route::get('incapacidades',        [PortalEmpleadoController::class, 'incapacidades']);
    Route::post('incapacidades',       [PortalEmpleadoController::class, 'registrarIncapacidad']);
    Route::get('incapacidades/{uuid}/soporte', [PortalEmpleadoController::class, 'soporteIncapacidad']);
    Route::get('entidades-medicas',     [PortalEmpleadoController::class, 'entidadesMedicas']);
    Route::get('licencias',            [PortalEmpleadoController::class, 'licencias']);
    Route::post('licencias',           [PortalEmpleadoController::class, 'solicitarLicencia']);
    Route::get('prestamos',            [SolicitudPrestamoController::class, 'portalIndex']);
    Route::post('prestamos',           [SolicitudPrestamoController::class, 'store']);
});

//Rutas tipos de contratos nomina
Route::prefix('nomina')->group(function () {
        
    Route::get('configuracion', [ConfiguracionNominaController::class, 'show']);
    Route::put('configuracion', [ConfiguracionNominaController::class, 'update']);
    Route::post('configuracion/firma', [ConfiguracionNominaController::class, 'subirFirma']);
    Route::get('horario-operacion/hoy', [HorarioOperacionDiariaController::class, 'show']);
    Route::put('horario-operacion/hoy', [HorarioOperacionDiariaController::class, 'update']);
    Route::get('horarios-usuario-semanales', [HorarioUsuarioSemanalController::class, 'index']);
    Route::post('horarios-usuario-semanales', [HorarioUsuarioSemanalController::class, 'store']);
    Route::apiResource('tipo-contratos', TipoContratoController::class);
    Route::get('contratacion/empleados', [ContratacionController::class, 'getEmpleados']);
    Route::get('contratacion/{uuid}/certificado', [ContratacionController::class, 'certificado']);
    Route::post('contratacion/{uuid}/certificado/enviar', [ContratacionController::class, 'enviarCertificado']);
    Route::patch('contratacion/{uuid}/estado', [ContratacionController::class, 'cambiarEstado']);
    Route::apiResource('contratacion-cambios', ContratacionCambioController::class)->only(['index', 'store', 'show']);
    Route::apiResource('contratacion', ContratacionController::class);
    Route::apiResource('ajustes-salariales', AjusteSalarialContratacionController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::apiResource('seguridad-social', SeguridadSocialController::class);
    Route::get('incapacidades/{uuid}/soporte', [IncapacidadController::class, 'soporte']);
    Route::apiResource('incapacidades', IncapacidadController::class);
    Route::patch('incapacidades/{uuid}/revisar', [IncapacidadController::class, 'revisar']);
    Route::apiResource('descuentos', DescuentoController::class);
    Route::get('solicitudes-prestamos', [SolicitudPrestamoController::class, 'index']);
    Route::patch('solicitudes-prestamos/{uuid}/aprobar', [SolicitudPrestamoController::class, 'aprobar']);
    Route::patch('solicitudes-prestamos/{uuid}/rechazar', [SolicitudPrestamoController::class, 'rechazar']);
    Route::apiResource('jornada-laborals', JornadaLaboralController::class);
    Route::apiResource('valores',ValorController::class);
    Route::get('parametros-laborales/vigente', [NominaParametroLaboralController::class, 'vigente']);
    Route::apiResource('parametros-laborales', NominaParametroLaboralController::class)->only(['index', 'store', 'update']);
    Route::apiResource('tipo-registros',TipoRegistroController::class);
    Route::get('users-face-photos/empleados-con-contrato', [UsersFacePhotoController::class, 'empleadosConContrato']);
    Route::get('users-face-photos/{uuid}/image', [UsersFacePhotoController::class, 'image']);
    Route::apiResource('users-face-photos',UsersFacePhotoController::class);
    Route::post('kiosko-devices/{uuid}/activation-link', [KioskoDeviceController::class, 'generateActivationLink']);
    Route::post('kiosko-devices/{uuid}/guest-link', [KioskoDeviceController::class, 'generateGuestLink']);
    Route::patch('kiosko-devices/{uuid}/revoke', [KioskoDeviceController::class, 'revoke']);
    Route::patch('kiosko-devices/{uuid}/deactivate', [KioskoDeviceController::class, 'deactivate']);
    Route::patch('kiosko-devices/{uuid}/activate-admin', [KioskoDeviceController::class, 'activateAdmin']);
    Route::apiResource('kiosko-devices',KioskoDeviceController::class);
    Route::apiResource('horario-laboral',HorarioLaboralController::class);
    Route::apiResource('transacional-registros',   TransacionalRegistroController::class);
    Route::get('transacional-registros/user/{userId}', [TransacionalRegistroController::class, 'byUser']);
    Route::get('work-sessions/resumen', [WorkSessionController::class, 'resumen']);
    Route::apiResource('work-sessions',    WorkSessionController::class);
    Route::patch('recuperaciones-tiempo/{uuid}/anular', [RecuperacionTiempoController::class, 'anular']);
    Route::apiResource('recuperaciones-tiempo', RecuperacionTiempoController::class)->only(['index', 'store']);
    Route::get('conceptos-contables/plantilla-puc-faltante', [NominaConceptoContableController::class, 'plantillaPucFaltante']);
    Route::post('conceptos-contables/sincronizar-puc', [NominaConceptoContableController::class, 'sincronizarPuc']);
    Route::apiResource('conceptos-contables', NominaConceptoContableController::class)->only(['index', 'show', 'update']);
    Route::get('nominas/resumen',              [NominaController::class, 'resumen']);
    Route::get('nominas/exportar-plano',        [NominaController::class, 'exportarPlano']);
    Route::post('nominas/preliquidar',         [NominaController::class, 'preliquidar']);
    Route::get('nominas/preliquidaciones/{uuid}', [NominaController::class, 'showPreliquidacion']);
    Route::post('nominas/preliquidaciones/{uuid}/ajustes', [NominaController::class, 'agregarAjustePreliquidacion']);
    Route::delete('nominas/preliquidaciones/{uuid}/ajustes/{ajusteUuid}', [NominaController::class, 'eliminarAjustePreliquidacion']);
    Route::patch('nominas/preliquidaciones/{uuid}/revision', [NominaController::class, 'enviarRevisionPreliquidacion']);
    Route::patch('nominas/preliquidaciones/{uuid}/aprobar', [NominaController::class, 'aprobarPreliquidacion']);
    Route::post('nominas/preliquidaciones/{uuid}/liquidar', [NominaController::class, 'liquidarPreliquidacion']);
    Route::post('nominas/preliquidar-retiro',  [LiquidacionRetiroController::class, 'preliquidar']);
    Route::post('nominas/liquidar-retiro',      [LiquidacionRetiroController::class, 'liquidar']);
    Route::get('liquidaciones-retiro', [LiquidacionRetiroController::class, 'index']);
    Route::get('liquidaciones-retiro/{uuid}/pdf', [LiquidacionRetiroController::class, 'pdf']);
    Route::middleware('es_responsable_del_departamento')->group(function () {
        Route::get('liquidaciones-prestaciones/tipos', [LiquidacionPrestacionController::class, 'tipos']);
        Route::get('liquidaciones-prestaciones/vacaciones-aprobadas/{userId}', [LiquidacionPrestacionController::class, 'vacacionesAprobadas']);
        Route::post('nominas/preliquidar-prestacion', [LiquidacionPrestacionController::class, 'preliquidar']);
        Route::post('nominas/liquidar-prestacion',    [LiquidacionPrestacionController::class, 'liquidar']);
        Route::get('liquidaciones-prestaciones',      [LiquidacionPrestacionController::class, 'index']);
    });
    Route::get('nominas/{uuid}/desprendible', [NominaController::class, 'desprendible']);
    Route::post('nominas/{uuid}/desprendible/enviar', [NominaController::class, 'enviarDesprendible']);
    Route::get('nominas/{uuid}/puc-payload', [NominaController::class, 'pucPayload']);
    Route::post('nominas/{uuid}/revertir', [NominaController::class, 'revertir']);
    Route::patch('nominas/{uuid}/aprobar-contabilidad', [NominaController::class, 'aprobarContabilidad']);
    Route::post('nominas/cerrar-periodo', [NominaController::class, 'cerrarPeriodo']);
    Route::get('nominas/exportar-puc/excel', [NominaController::class, 'exportarPucExcel']);
    Route::get('nominas/exportar-puc/pdf', [NominaController::class, 'exportarPucPdf']);
    Route::apiResource('nominas', NominaController::class)->only(['index', 'show']);

    Route::get('horas-extras/exportar', [HoraExtraController::class, 'exportar']);
    Route::patch('horas-extras/aprobar-todas', [HoraExtraController::class, 'aprobarTodas']);
    Route::apiResource('horas-extras', HoraExtraController::class)->except(['update']);
    Route::patch('horas-extras/{uuid}/aprobar',  [HoraExtraController::class, 'aprobar']);
     Route::patch('horas-extras/{uuid}/rechazar', [HoraExtraController::class, 'rechazar']);

     Route::apiResource('comisiones', ComisionController::class);
     Route::patch('comisiones/{uuid}/aprobar',  [ComisionController::class, 'aprobar']);
     Route::patch('comisiones/{uuid}/rechazar', [ComisionController::class, 'rechazar']);

     Route::apiResource('novedades-retroactivas', NovedadRetroactivaController::class)->except(['update']);
     Route::patch('novedades-retroactivas/{uuid}/aprobar',  [NovedadRetroactivaController::class, 'aprobar']);
     Route::patch('novedades-retroactivas/{uuid}/rechazar', [NovedadRetroactivaController::class, 'rechazar']);

     Route::apiResource('permisos', PermisoController::class)->except(['update']);
     Route::patch('permisos/{uuid}/aprobar',  [PermisoController::class, 'aprobar']);
     Route::patch('permisos/{uuid}/rechazar', [PermisoController::class, 'rechazar']);

     Route::apiResource('licencias', LicenciaController::class)->except(['update']);
     Route::patch('licencias/{uuid}/aprobar',  [LicenciaController::class, 'aprobar']);
     Route::patch('licencias/{uuid}/rechazar', [LicenciaController::class, 'rechazar']);

     Route::middleware('es_responsable_del_departamento')->group(function () {
         Route::get('vacaciones/resumen/{userId}', [VacacionController::class, 'resumen']);
         Route::apiResource('vacaciones', VacacionController::class);
         Route::patch('vacaciones/{uuid}/aprobar',  [VacacionController::class, 'aprobar']);
         Route::patch('vacaciones/{uuid}/rechazar', [VacacionController::class, 'rechazar']);
     });

     Route::get('llamados-atencion/{uuid}/pdf', [LlamadoAtencionController::class, 'pdf']);
     Route::apiResource('llamados-atencion', LlamadoAtencionController::class)->except(['update']);

     Route::get('descargos/{uuid}/pdf', [DescargoController::class, 'pdf']);
     Route::apiResource('descargos', DescargoController::class)->except(['update']);
});


Route::apiResource('reportes-bic', ReporteBicConttroller::class);


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
Route::put('update/{id}', [UsuarioController::class, 'update'])->middleware('auth:sanctum');

Route::post('contacto', [PqrController::class, 'contacto']);
//rutas crm
Route::get('/seguimientos', [SeguimientoController::class, 'index']);


Route::get('procesos/departamento/{departamento_id}', [ProcesoController::class, 'index']);

Route::get('download/{id}', [DocumentosAdministrativosController::class, 'downloand']);
Route::delete('documentos-administrativos/{id}', [DocumentosAdministrativosController::class, 'destroy'])
    ->middleware('auth:sanctum');
Route::apiResource('carpetas', CarpetaController::class)
    ->middleware('auth:sanctum');
Route::put('carpetas/{id}/mover', [CarpetaController::class, 'mover'])
    ->middleware('auth:sanctum');
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
Route::get('obtener-reportes-bic', [ReporteBicConttroller::class, 'index']);
