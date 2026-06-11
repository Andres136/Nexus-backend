<?php

namespace App\Services\contabilidad;

use App\Models\contabilidad\Puck;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PuckService
{
    //Crear puck

    public function listar()
    {
        return Puck::orderByRaw('LENGTH(numero), numero')->get();
    }   


    public function create (array $data)
    {
        // Lógica para crear un nuevo recurso
        $data['numero'] = $this->normalizarCodigo($data['numero']);
        $data['nivel'] = $this->determinarNivel($data['numero']);
        $data['permite_movimiento'] ??= strlen($data['numero']) >= 6;
        $puck = Puck::create($data);
        $this->sincronizarJerarquia();

        return $puck->refresh();

    }

   public function update(array $data, int $id)
{
    $puck = Puck::findOrFail($id);

    $data['numero'] = $this->normalizarCodigo($data['numero']);
    $data['nivel'] = $this->determinarNivel($data['numero']);
    $puck->update($data);
    $this->sincronizarJerarquia();

    return $puck->refresh();
}

    public function delete (int $id)
    {
        // Lógica para eliminar un recurso
        $puck = Puck::findOrFail($id);
        $puck->delete();
       return $puck;
    }

    //tAER POER ID
    public function getById (int $id)
    {
        // Lógica para obtener un recurso por su ID
        return Puck::find($id);
        


    }   

    public function importar(UploadedFile $file): array
    {
        $rows = Excel::toCollection(null, $file)->first();
        $created = 0;
        $updated = 0;
        $errors = [];

        DB::transaction(function () use ($rows, &$created, &$updated, &$errors) {
            foreach ($rows->skip(1) as $index => $row) {
                $excelRow = $index + 2;
                $codigo = $this->normalizarCodigo($row[0] ?? '');
                $nombre = trim((string) ($row[1] ?? ''));

                if ($codigo === '' && $nombre === '') {
                    continue;
                }

                if ($codigo === '' || !ctype_digit($codigo) || strlen($codigo) > 20) {
                    $errors[] = ['fila' => $excelRow, 'error' => 'El código debe contener únicamente números y máximo 20 dígitos.'];
                    continue;
                }

                if ($nombre === '') {
                    $errors[] = ['fila' => $excelRow, 'error' => 'El nombre es obligatorio.'];
                    continue;
                }

                $naturaleza = $this->normalizarNaturaleza($row[2] ?? null);
                if (($row[2] ?? null) && $naturaleza === null) {
                    $errors[] = ['fila' => $excelRow, 'error' => 'La naturaleza debe ser débito o crédito.'];
                    continue;
                }

                $account = Puck::firstOrNew(['numero' => $codigo]);
                $account->fill([
                    'nombre' => $nombre,
                    'nivel' => $this->determinarNivel($codigo),
                    'naturaleza' => $naturaleza,
                    'descripcion' => $this->normalizarTextoOpcional($row[3] ?? null),
                    'dinamica' => $this->normalizarTextoOpcional($row[4] ?? null),
                    'permite_movimiento' => strlen($codigo) >= 6,
                    'activo' => true,
                ]);

                $account->exists ? $updated++ : $created++;
                $account->save();
            }

            $this->sincronizarJerarquia();
        });

        return compact('created', 'updated', 'errors');
    }

    private function normalizarCodigo(mixed $codigo): string
    {
        return preg_replace('/\.0$/', '', preg_replace('/[\s.-]+/', '', trim((string) $codigo)));
    }

    private function normalizarNaturaleza(mixed $naturaleza): ?string
    {
        $value = strtolower(trim((string) $naturaleza));

        return match ($value) {
            'd', 'debito', 'débito' => 'debito',
            'c', 'credito', 'crédito' => 'credito',
            '', null => null,
            default => null,
        };
    }

    private function normalizarTextoOpcional(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function determinarNivel(string $codigo): string
    {
        return match (strlen($codigo)) {
            1 => 'clase',
            2 => 'grupo',
            4 => 'cuenta',
            6 => 'subcuenta',
            default => 'auxiliar',
        };
    }

    private function determinarCodigoPadre(string $codigo): ?string
    {
        return match (strlen($codigo)) {
            2 => substr($codigo, 0, 1),
            4 => substr($codigo, 0, 2),
            6 => substr($codigo, 0, 4),
            default => strlen($codigo) > 6 ? substr($codigo, 0, 6) : null,
        };
    }

    private function sincronizarJerarquia(): void
    {
        $accounts = Puck::orderByRaw('LENGTH(numero), numero')->get()->keyBy('numero');

        foreach ($accounts as $account) {
            $parentCode = $this->determinarCodigoPadre($account->numero);
            $parentId = $parentCode ? $accounts->get($parentCode)?->id : null;

            if ($account->parent_id !== $parentId) {
                $account->parent_id = $parentId;
                $account->save();
            }
        }
    }
}
