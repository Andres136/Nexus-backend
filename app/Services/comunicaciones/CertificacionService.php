<?php

namespace App\Services\comunicaciones;

use App\Models\comunicaciones\Certificacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CertificacionService
{
    public function all($search = null, $limit = 50, $activo = null)
    {
        $query = Certificacion::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', "%{$search}%")
                    ->orWhere('subtitulo', 'like', "%{$search}%");
            });
        }

        if ($activo !== null) {
            $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('titulo')->limit($limit)->get();
    }

    public function find($id): Certificacion
    {
        return Certificacion::where('uuid', $id)
            ->orWhere('id', $id)
            ->firstOrFail();
    }

    public function create(array $data): Certificacion
    {
        if (($data['logo'] ?? null) instanceof UploadedFile) {
            $data['logo'] = $this->storeLogo($data['logo']);
        } else {
            unset($data['logo']);
        }

        return Certificacion::create($data);
    }

    public function update(Certificacion $certificacion, array $data): Certificacion
    {
        if (($data['logo'] ?? null) instanceof UploadedFile) {
            $this->deleteLogo($certificacion->logo);
            $data['logo'] = $this->storeLogo($data['logo']);
        } else {
            unset($data['logo']);
        }

        $certificacion->update($data);

        return $certificacion;
    }

    public function toggleActivo(Certificacion $certificacion): Certificacion
    {
        $certificacion->update(['activo' => ! $certificacion->activo]);

        return $certificacion;
    }

    public function delete(Certificacion $certificacion): bool
    {
        return (bool) $certificacion->delete();
    }

    private function storeLogo(UploadedFile $file): string
    {
        return $file->store('certificaciones', 'public');
    }

    private function deleteLogo(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
