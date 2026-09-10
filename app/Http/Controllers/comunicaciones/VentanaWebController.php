<?php

namespace App\Http\Controllers\comunicaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\comunicaciones\VentanaWebRegistroRequest;
use App\Http\Requests\comunicaciones\VentanaWebRequest;
use App\Services\comunicaciones\VentanaWebService;
use Illuminate\Http\Request;

class VentanaWebController extends Controller
{
    protected $ventanaWebService;

    public function __construct(VentanaWebService $ventanaWebService)
    {
        $this->ventanaWebService = $ventanaWebService;
    }

    /**
     * Configuración actual (panel admin).
     */
    public function show()
    {
        return response()->json($this->ventanaWebService->obtener());
    }

    /**
     * Actualizar la configuración de la ventana.
     */
    public function update(VentanaWebRequest $request)
    {
        $ventana = $this->ventanaWebService->actualizar($request->validated());
        return response()->json([
            'message' => 'Ventana web actualizada exitosamente',
            'data' => $ventana,
        ]);
    }

    /**
     * Configuración pública: solo se expone si la ventana está activa.
     */
    public function publica()
    {
        $ventana = $this->ventanaWebService->obtener();

        if (! $ventana->activo) {
            return response()->json(['activo' => false]);
        }

        return response()->json([
            'activo' => true,
            'uuid' => $ventana->uuid,
            'titulo' => $ventana->titulo,
            'subtitulo' => $ventana->subtitulo,
            'contenido' => $ventana->contenido,
            'imagen_url' => $ventana->imagen_url,
            'boton_activo' => $ventana->boton_activo,
            'boton_texto' => $ventana->boton_texto,
        ]);
    }

    /**
     * Guardar un registro enviado desde el formulario público.
     */
    public function registrar(VentanaWebRegistroRequest $request)
    {
        $this->ventanaWebService->registrar($request->validated());
        return response()->json([
            'message' => '¡Registro enviado exitosamente!',
        ], 201);
    }

    /**
     * Listado de registros capturados (panel admin).
     */
    public function registros(Request $request)
    {
        $registros = $this->ventanaWebService->registros(
            $request->input('search'),
            $request->has('leido') ? $request->boolean('leido') : null,
            $request->input('per_page', 50)
        );
        return response()->json($registros);
    }

    /**
     * Marcar / desmarcar un registro como leído.
     */
    public function marcarLeido(Request $request, string $id)
    {
        $registro = $this->ventanaWebService->buscarRegistro($id);
        $leido = $request->has('leido') ? $request->boolean('leido') : true;
        $actualizado = $this->ventanaWebService->marcarLeido($registro, $leido);
        return response()->json([
            'message' => $actualizado->leido ? 'Marcado como leído' : 'Marcado como no leído',
            'data' => $actualizado,
        ]);
    }

    /**
     * Eliminar un registro.
     */
    public function eliminarRegistro(string $id)
    {
        $registro = $this->ventanaWebService->buscarRegistro($id);
        $this->ventanaWebService->eliminarRegistro($registro);
        return response()->json(['message' => 'Registro eliminado']);
    }
}
