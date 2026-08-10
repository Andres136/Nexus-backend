<?php

namespace Tests\Unit;

use App\Models\DocumentoMaestro;
use App\Services\DocumentoMaestroService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentoMaestroServiceTest extends TestCase
{
    private DocumentoMaestroService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('departamentos', function ($table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('documentos_maestros', function ($table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tipo_documento');
            $table->string('codigo');
            $table->date('fecha_emision');
            $table->date('fecha_actualizacion')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('medio_fisico')->nullable();
            $table->string('medio_digital')->nullable();
            $table->string('retencion_gestion')->nullable();
            $table->string('retencion_central')->nullable();
            $table->string('disposicion_final')->nullable();
            $table->timestamps();

            $table->unique(['departamento_id', 'codigo']);
        });

        $this->service = new DocumentoMaestroService();
    }

    private function crearDepartamento(string $nombre = 'Marketing-Comunicaciones'): int
    {
        return DB::table('departamentos')->insertGetId([
            'nombre' => $nombre,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearUsuario(string $nombre = 'John Sanabria'): int
    {
        return DB::table('users')->insertGetId([
            'name' => $nombre,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function datosDocumento(int $departamentoId, int $userId, array $overrides = []): array
    {
        return array_merge([
            'departamento_id' => $departamentoId,
            'user_id' => $userId,
            'tipo_documento' => 'FORMATO',
            'codigo' => 'GGE-FO-01',
            'fecha_emision' => '2024-12-26',
            'fecha_actualizacion' => '2026-02-17',
            'version' => 4,
            'medio_fisico' => 'Administración',
            'medio_digital' => 'https://docs.google.com/spreadsheets/d/1U',
            'retencion_gestion' => '2 años',
            'retencion_central' => '5 años',
            'disposicion_final' => 'Conservación histórica permanente',
        ], $overrides);
    }

    public function test_crear_registra_el_documento_con_su_departamento_y_lider(): void
    {
        $departamentoId = $this->crearDepartamento();
        $userId = $this->crearUsuario();

        $documento = $this->service->crear($this->datosDocumento($departamentoId, $userId));

        $this->assertInstanceOf(DocumentoMaestro::class, $documento);
        $this->assertSame('GGE-FO-01', $documento->codigo);
        $this->assertSame($departamentoId, $documento->departamento_id);
        $this->assertSame($userId, $documento->user_id);
        $this->assertDatabaseCount('documentos_maestros', 1);
    }

    public function test_listar_por_departamento_solo_devuelve_documentos_de_ese_departamento(): void
    {
        $marketing = $this->crearDepartamento('Marketing-Comunicaciones');
        $hseq = $this->crearDepartamento('Hseq');
        $userId = $this->crearUsuario();

        $this->service->crear($this->datosDocumento($marketing, $userId, ['codigo' => 'MKT-FO-01']));
        $this->service->crear($this->datosDocumento($hseq, $userId, ['codigo' => 'HSEQ-FO-01']));

        $resultado = $this->service->listarPorDepartamento($marketing);

        $this->assertCount(1, $resultado);
        $this->assertSame('MKT-FO-01', $resultado->first()->codigo);
    }

    public function test_listar_por_departamento_ordena_por_codigo(): void
    {
        $departamentoId = $this->crearDepartamento();
        $userId = $this->crearUsuario();

        $this->service->crear($this->datosDocumento($departamentoId, $userId, ['codigo' => 'GGE-FO-02']));
        $this->service->crear($this->datosDocumento($departamentoId, $userId, ['codigo' => 'GGE-FO-01']));

        $resultado = $this->service->listarPorDepartamento($departamentoId);

        $this->assertSame(['GGE-FO-01', 'GGE-FO-02'], $resultado->pluck('codigo')->all());
    }

    public function test_obtener_devuelve_el_documento_con_sus_relaciones(): void
    {
        $departamentoId = $this->crearDepartamento();
        $userId = $this->crearUsuario();
        $creado = $this->service->crear($this->datosDocumento($departamentoId, $userId));

        $documento = $this->service->obtener($creado->id);

        $this->assertTrue($documento->relationLoaded('departamento'));
        $this->assertTrue($documento->relationLoaded('liderProceso'));
    }

    public function test_obtener_lanza_excepcion_si_el_documento_no_existe(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener(999);
    }

    public function test_actualizar_modifica_los_campos_del_documento(): void
    {
        $departamentoId = $this->crearDepartamento();
        $userId = $this->crearUsuario();
        $creado = $this->service->crear($this->datosDocumento($departamentoId, $userId));

        $actualizado = $this->service->actualizar($creado->id, [
            'version' => 5,
            'disposicion_final' => 'Eliminación controlada',
        ]);

        $this->assertSame(5, $actualizado->version);
        $this->assertSame('Eliminación controlada', $actualizado->disposicion_final);
        $this->assertDatabaseHas('documentos_maestros', [
            'id' => $creado->id,
            'version' => 5,
        ]);
    }

    public function test_eliminar_borra_el_documento(): void
    {
        $departamentoId = $this->crearDepartamento();
        $userId = $this->crearUsuario();
        $creado = $this->service->crear($this->datosDocumento($departamentoId, $userId));

        $this->service->eliminar($creado->id);

        $this->assertDatabaseMissing('documentos_maestros', ['id' => $creado->id]);
    }

    public function test_eliminar_lanza_excepcion_si_el_documento_no_existe(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->eliminar(999);
    }
}
