<?php

namespace App\Services\Corporate;

use App\Models\Corporate\CorporateDocument;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CorporateDocumentService
{
    public function publicList()
    {
        return CorporateDocument::query()
            ->with(['features', 'benefits'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn (CorporateDocument $document) => $this->formatPublic($document));
    }

    public function adminList(array $filters = [])
    {
        return CorporateDocument::query()
            ->with(['features', 'benefits'])
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('subtitle', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (CorporateDocument $document) => $this->formatAdmin($document));
    }

    public function publicShow(string $slug): array
    {
        $document = CorporateDocument::with(['features', 'benefits'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->formatPublic($document);
    }

    public function adminShow(int $id): array
    {
        $document = CorporateDocument::with(['features', 'benefits'])->findOrFail($id);

        return $this->formatAdmin($document);
    }

    public function store(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $file = $data['file'];
            $path = $this->storeFile($file);

            $document = CorporateDocument::create([
                ...$this->documentPayload($data),
                'slug' => $this->uniqueSlug($data['slug'] ?? null, $data['title']),
                'file_path' => $path,
                'file_size' => $this->humanFileSize($file->getSize()),
                'file_type' => strtoupper($file->getClientOriginalExtension() ?: 'PDF'),
            ]);

            $this->syncList($document, 'features', $data['features'] ?? []);
            $this->syncList($document, 'benefits', $data['benefits'] ?? []);

            return $this->adminShow($document->id);
        });
    }

    public function update(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $document = CorporateDocument::lockForUpdate()->findOrFail($id);
            $payload = $this->documentPayload($data);
            $payload['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['title'], $document->id);

            if (!empty($data['file']) && $data['file'] instanceof UploadedFile) {
                Storage::disk('public')->delete($document->file_path);
                $payload['file_path'] = $this->storeFile($data['file']);
                $payload['file_size'] = $this->humanFileSize($data['file']->getSize());
                $payload['file_type'] = strtoupper($data['file']->getClientOriginalExtension() ?: 'PDF');
            }

            $document->update($payload);
            $this->syncList($document, 'features', $data['features'] ?? []);
            $this->syncList($document, 'benefits', $data['benefits'] ?? []);

            return $this->adminShow($document->id);
        });
    }

    public function deactivate(int $id): void
    {
        CorporateDocument::findOrFail($id)->update(['is_active' => false]);
    }

    public function registerDownload(string $slug, Request $request): array
    {
        return DB::transaction(function () use ($slug, $request) {
            $document = CorporateDocument::where('slug', $slug)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $document->downloads()->create([
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 2000),
                'referer' => substr((string) $request->headers->get('referer'), 0, 500),
                'created_at' => now(),
            ]);

            $document->increment('downloads_count');
            $document->refresh();

            return [
                'slug' => $document->slug,
                'download_url' => $this->fileUrl($document->file_path),
                'downloads_count' => $document->downloads_count,
            ];
        });
    }

    private function documentPayload(array $data): array
    {
        return Arr::only($data, [
            'title',
            'subtitle',
            'description',
            'long_description',
            'icon',
            'pages',
            'last_update',
            'category',
            'theme',
            'sort_order',
            'is_active',
        ]);
    }

    private function syncList(CorporateDocument $document, string $relation, array $items): void
    {
        $document->{$relation}()->delete();

        collect($items)
            ->map(fn ($text) => is_array($text) ? ($text['text'] ?? '') : $text)
            ->map(fn ($text) => trim((string) $text))
            ->filter()
            ->values()
            ->each(function (string $text, int $index) use ($document, $relation) {
                $document->{$relation}()->create([
                    'text' => $text,
                    'sort_order' => $index,
                ]);
            });
    }

    private function storeFile(UploadedFile $file): string
    {
        $name = Str::uuid() . '.' . ($file->getClientOriginalExtension() ?: 'pdf');

        return $file->storeAs('corporate_documents', $name, 'public');
    }

    private function uniqueSlug(?string $slug, string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $title);
        $candidate = $baseSlug;
        $index = 2;

        while (
            CorporateDocument::where('slug', $candidate)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = "{$baseSlug}-{$index}";
            $index++;
        }

        return $candidate;
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1024, 1) . ' KB';
    }

    private function formatPublic(CorporateDocument $document): array
    {
        return [
            'slug' => $document->slug,
            'title' => $document->title,
            'subtitle' => $document->subtitle,
            'description' => $document->description,
            'long_description' => $document->long_description,
            'icon' => $document->icon,
            'file_type' => $document->file_type,
            'file_size' => $document->file_size,
            'pages' => $document->pages,
            'last_update' => optional($document->last_update)->format('Y-m-d'),
            'category' => $document->category,
            'theme' => $document->theme,
            'download_url' => $this->fileUrl($document->file_path),
            'preview_url' => $this->fileUrl($document->file_path),
            'downloads_count' => $document->downloads_count,
            'features' => $document->features->pluck('text')->values(),
            'benefits' => $document->benefits->pluck('text')->values(),
        ];
    }

    private function formatAdmin(CorporateDocument $document): array
    {
        return [
            ...$this->formatPublic($document),
            'id' => $document->id,
            'is_active' => $document->is_active,
            'sort_order' => $document->sort_order,
        ];
    }

    private function fileUrl(string $path): string
    {
        return url(Storage::disk('public')->url($path));
    }
}
