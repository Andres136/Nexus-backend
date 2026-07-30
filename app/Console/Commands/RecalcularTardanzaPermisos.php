<?php

namespace App\Console\Commands;

use App\Models\Nomina\Permiso;
use App\Models\Nomina\WorkSession;
use App\Services\Nomina\WorkSessionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;

class RecalcularTardanzaPermisos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nomina:recalcular-tardanza-permisos
        {--desde= : Fecha inicial YYYY-MM-DD (por defecto 60 días atrás)}
        {--hasta= : Fecha final YYYY-MM-DD (por defecto hoy)}
        {--apply : Guarda los cambios. Sin esta bandera solo se muestra un reporte (dry-run)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula minutos_tardanza de sesiones con permiso aprobado afectadas por el bug de exoneración todo-o-nada (corregido en WorkSessionService::calcularMinutos)';

    public function handle(): int
    {
        $desde = $this->option('desde') ? Carbon::parse($this->option('desde')) : now()->subDays(60);
        $hasta = $this->option('hasta') ? Carbon::parse($this->option('hasta')) : now();
        $apply = (bool) $this->option('apply');

        $service = app(WorkSessionService::class);
        $calcularMinutos = new ReflectionMethod($service, 'calcularMinutos');
        $calcularMinutos->setAccessible(true);

        $sesiones = WorkSession::with(['jornadaLaboral', 'empleado:id,name'])
            ->whereBetween('registro_diario', [$desde->toDateString(), $hasta->toDateString()])
            ->where('minutos_tardanza', '>', 0)
            ->whereNotNull('hora_entrada')
            ->get();

        $this->info("Revisando {$sesiones->count()} sesiones con tardanza entre {$desde->toDateString()} y {$hasta->toDateString()}...");

        $filas = [];

        foreach ($sesiones as $sesion) {
            $entradaReal = Carbon::parse($sesion->hora_entrada);

            $tienePermiso = Permiso::where('user_id', $sesion->user_id)
                ->whereDate('fecha', $sesion->registro_diario)
                ->where('status', 'aprobado')
                ->whereIn('tipo', ['llegada_tarde', 'ausencia_parcial'])
                ->whereTime('hora_inicio', '<=', $entradaReal->format('H:i:00'))
                ->exists();

            // Sin permiso ese día no hay nada que este bug pudiera haber afectado.
            if (! $tienePermiso) {
                continue;
            }

            /** @var array $resultado */
            $resultado = $calcularMinutos->invoke($service, ['user_id' => $sesion->user_id], $sesion);
            $nuevaTardanza = (int) ($resultado['minutos_tardanza'] ?? 0);

            if ($nuevaTardanza === (int) $sesion->minutos_tardanza) {
                continue;
            }

            $filas[] = [
                'uuid' => $sesion->uuid,
                'empleado' => $sesion->empleado?->name ?? $sesion->user_id,
                'fecha' => $sesion->registro_diario->toDateString(),
                'hora_entrada' => $entradaReal->format('H:i'),
                'tardanza_actual' => $sesion->minutos_tardanza,
                'tardanza_corregida' => $nuevaTardanza,
            ];

            if ($apply) {
                DB::transaction(function () use ($sesion, $nuevaTardanza) {
                    $anterior = $sesion->minutos_tardanza;
                    $sesion->update(['minutos_tardanza' => $nuevaTardanza]);

                    Log::info('Tardanza recalculada por corrección de permiso todo-o-nada', [
                        'uuid' => $sesion->uuid,
                        'user_id' => $sesion->user_id,
                        'minutos_tardanza_anterior' => $anterior,
                        'minutos_tardanza_nuevo' => $nuevaTardanza,
                    ]);
                });
            }
        }

        if (empty($filas)) {
            $this->info('No se encontraron sesiones afectadas en el rango indicado.');

            return self::SUCCESS;
        }

        $this->table(
            ['UUID', 'Empleado', 'Fecha', 'Entrada', 'Tardanza actual', 'Tardanza corregida'],
            $filas
        );

        if ($apply) {
            $this->info(count($filas).' sesión(es) corregida(s).');
        } else {
            $this->warn(count($filas).' sesión(es) afectada(s) — modo dry-run, no se guardó nada. Vuelve a ejecutar con --apply para corregirlas.');
        }

        return self::SUCCESS;
    }
}
