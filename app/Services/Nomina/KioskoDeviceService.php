<?php

namespace App\Services\Nomina;

use App\Models\Nomina\KioskoDevice;
use App\Models\Nomina\JornadaLaboral;
use App\Models\Nomina\UsersFacePhoto;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LogicException;

class KioskoDeviceService
{
    private const WITH = ['sede', 'bodega', 'tipoRegistro'];
    private const ACTIVATION_TTL_HOURS = 24;
    private const GUEST_TTL_MINUTES    = 20;
    private const BOOTSTRAP_CACHE_KEY = 'nomina:kiosko:bootstrap-catalog:v1';
    private const BOOTSTRAP_CACHE_TTL_MINUTES = 10;

    public function getAll(array $filters = []): LengthAwarePaginator|Collection
    {
        $perPage = $filters['per_page'] ?? 10;

        $query = KioskoDevice::with(self::WITH)
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('ip_adres', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['sede_id']), fn($q) => $q->where('sede_id', $filters['sede_id']))
            ->orderByDesc('created_at');

        // Modo sin paginar: usado por selectores que necesitan el listado
        // completo de kioskos (no la tabla administrativa paginada).
        if (!empty($filters['all'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    public function getByUuid(string $uuid): KioskoDevice
    {
        return KioskoDevice::with(self::WITH)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

public function create(array $data): KioskoDevice
{
    return DB::transaction(function () use ($data) {

        $ultimoId = KioskoDevice::max('id') + 1;

        $data['code'] = 'KIOSK-' . str_pad($ultimoId, 4, '0', STR_PAD_LEFT);

        $activation = $this->buildToken();

        $data['activation_token_hash'] = $this->hashToken($activation['plain']);
        $data['activation_expires_at'] = now()->addHours(self::ACTIVATION_TTL_HOURS);
        $data['status'] = 'pending';

        $device = KioskoDevice::create($data);

    

        $device->setAttribute('activation_url', $this->activationUrl($activation['plain']));

        return $device->load(self::WITH);
    });
}

    public function update(string $uuid, array $data): KioskoDevice
    {
        return DB::transaction(function () use ($uuid, $data) {
            $device = KioskoDevice::where('uuid', $uuid)->firstOrFail();

            $device->update($data);

            Log::info('Dispositivo kiosko actualizado', ['uuid' => $device->uuid]);

            return $device->fresh(self::WITH);
        });
    }

    public function generateActivationLink(string $uuid): array
    {
        return DB::transaction(function () use ($uuid) {
            $device = KioskoDevice::where('uuid', $uuid)->firstOrFail();
            $token = $this->buildToken();

            $device->update([
                'activation_token_hash' => $this->hashToken($token['plain']),
                'activation_expires_at' => now()->addHours(self::ACTIVATION_TTL_HOURS),
                'activation_used_at' => null,
                'device_session_token_hash' => null,
                'device_fingerprint_hash' => null,
                'activated_at' => null,
                'status' => 'pending',
                'revoked_at' => null,
            ]);

            Log::info('Link de activación de kiosko generado', ['uuid' => $device->uuid]);

            return [
                'device' => $device->fresh(self::WITH),
                'activation_url' => $this->activationUrl($token['plain']),
                'expires_at' => $device->activation_expires_at,
            ];
        });
    }

    public function activateDevice(array $data, ?string $ip = null): array
    {
        return DB::transaction(function () use ($data, $ip) {
            $activationHash = $this->hashToken($data['token']);

            $device = KioskoDevice::where('activation_token_hash', $activationHash)
                ->firstOrFail();

            if ($device->activation_used_at) {
                throw new LogicException('Este link de activación ya fue usado.');
            }

            if (!$device->activation_expires_at || $device->activation_expires_at->isPast()) {
                throw new LogicException('Este link de activación venció.');
            }

            if ($device->revoked_at || $device->status === 'revoked') {
                throw new LogicException('Este dispositivo fue revocado.');
            }

            $sessionToken = $this->buildToken();
            $fingerprintHash = $this->hashToken($data['fingerprint']);

            $device->update([
                'activation_used_at' => now(),
                'device_session_token_hash' => $this->hashToken($sessionToken['plain']),
                'device_fingerprint_hash' => $fingerprintHash,
                'activated_at' => now(),
                'last_seen_at' => now(),
                'last_ip' => $ip,
                'status' => 'active',
                'revoked_at' => null,
            ]);

            Log::info('Kiosko activado', ['uuid' => $device->uuid, 'ip' => $ip]);

            return [
                'device' => $device->fresh(self::WITH),
                'session_token' => $sessionToken['plain'],
                'kiosko_url' => $this->kioskoUrl($device->uuid),
            ];
        });
    }

    public function validateDeviceSession(array $data, ?string $ip = null): KioskoDevice
    {
        if (empty($data['uuid']) || empty($data['session_token']) || empty($data['fingerprint'])) {
            throw new AuthorizationException('Faltan credenciales del kiosko.');
        }

        $device = KioskoDevice::with(self::WITH)
            ->where('uuid', $data['uuid'])
            ->firstOrFail();

        $this->assertSessionIsValid(
            $device,
            $data['session_token'],
            $data['fingerprint'],
            $data['fingerprint_candidates'] ?? []
        );

        $device->forceFill([
            'last_seen_at' => now(),
            'last_ip' => $ip,
        ])->save();

        return $device->fresh(self::WITH);
    }

    public function bootstrapDeviceSession(array $data, ?string $ip = null): array
    {
        $device = $this->validateDeviceSession($data, $ip);
        $catalog = $this->bootstrapCatalog();

        return [
            'device' => $device,
            ...$catalog,
        ];
    }

    public function generateGuestLink(string $uuid): array
    {
        return DB::transaction(function () use ($uuid) {
            $device = KioskoDevice::where('uuid', $uuid)->firstOrFail();

            if ($device->status === 'revoked') {
                throw new LogicException('El dispositivo está revocado.');
            }

            $token = $this->buildToken();

            $device->update([
                'guest_token_hash' => $this->hashToken($token['plain']),
                'guest_expires_at' => now()->addMinutes(self::GUEST_TTL_MINUTES),
                'guest_used_at' => null,
                'guest_fingerprint_hash' => null,
            ]);

            Log::info('Link de acceso temporal de kiosko generado', ['uuid' => $device->uuid]);

            return [
                'device'     => $device->fresh(self::WITH),
                'guest_url'  => $this->guestUrl($device->uuid, $token['plain']),
                'expires_at' => $device->fresh()->guest_expires_at,
            ];
        });
    }

    public function validateGuestAccess(
        string $uuid,
        string $guestToken,
        ?string $fingerprint = null,
        bool $consume = false
    ): KioskoDevice
    {
        $device = KioskoDevice::with(self::WITH)->where('uuid', $uuid)->firstOrFail();

        if ($device->status === 'revoked' || $device->revoked_at) {
            throw new AuthorizationException('El kiosko fue revocado.');
        }

        if (!$device->guest_token_hash) {
            throw new AuthorizationException('No hay un link de acceso temporal activo para este kiosko.');
        }

        if (!hash_equals($device->guest_token_hash, $this->hashToken($guestToken))) {
            throw new AuthorizationException('El link de acceso temporal no es válido.');
        }

        if (!$device->guest_expires_at || $device->guest_expires_at->isPast()) {
            throw new AuthorizationException('El link de acceso temporal ha vencido.');
        }

        if ($consume && !$device->guest_used_at) {
            if (!$fingerprint) {
                throw new AuthorizationException('No fue posible identificar este dispositivo.');
            }

            $device->forceFill([
                'guest_used_at' => now(),
                'guest_fingerprint_hash' => $this->hashToken($fingerprint),
            ])->save();

            return $device->fresh(self::WITH);
        }

        if ($device->guest_used_at) {
            if (!$fingerprint) {
                throw new AuthorizationException('Este link temporal ya fue usado en otro dispositivo.');
            }

            if (!$device->guest_fingerprint_hash || !hash_equals($device->guest_fingerprint_hash, $this->hashToken($fingerprint))) {
                throw new AuthorizationException('Este link temporal ya fue usado en otro dispositivo.');
            }
        }

        return $device;
    }

    public function bootstrapGuestSession(array $data): array
    {
        $device = $this->validateGuestAccess(
            $data['uuid'],
            $data['guest_token'],
            $data['fingerprint'] ?? null,
            true
        );

        $catalog = $this->bootstrapCatalog();

        return [
            'device'    => $device,
            ...$catalog,
        ];
    }

    public static function clearBootstrapCache(): void
    {
        Cache::forget(self::BOOTSTRAP_CACHE_KEY);
    }

    private function bootstrapCatalog(): array
    {
        return Cache::remember(
            self::BOOTSTRAP_CACHE_KEY,
            now()->addMinutes(self::BOOTSTRAP_CACHE_TTL_MINUTES),
            function () {
                $empleados = User::select(
                    'users.id',
                    'users.name',
                    DB::raw('(SELECT c.numero_documento FROM contrataciones c WHERE c.users_id = users.id ORDER BY c.id DESC LIMIT 1) as numero_documento')
                )
                    ->whereHas('contratacionActivaNomina')
                    ->orderBy('users.name')
                    ->get();

                return [
                    'empleados' => $empleados,
                    'fotos' => UsersFacePhoto::with('empleado:id,name')
                        ->whereHas('empleado.contratacionActivaNomina')
                        ->get(),
                    'jornadas' => JornadaLaboral::orderByDesc('status')
                        ->orderBy('nombre')
                        ->get(),
                ];
            }
        );
    }

    // Valida sesión de dispositivo físico o acceso temporal (guest token).
    // Usado por los controllers de kiosko públicos para auth dual.
    public function resolveKioskoDevice(\Illuminate\Http\Request $request, ?string $ip = null): KioskoDevice
    {
        $guestToken = $request->header('X-Kiosko-Guest-Token');
        $deviceUuid = (string) $request->header('X-Kiosko-Device');

        if ($guestToken && $deviceUuid) {
            return $this->validateGuestAccess(
                $deviceUuid,
                $guestToken,
                $request->header('X-Kiosko-Guest-Fingerprint')
            );
        }

        return $this->validateDeviceSession([
            'uuid'          => $deviceUuid,
            'session_token' => (string) $request->header('X-Kiosko-Session'),
            'fingerprint'   => (string) $request->header('X-Kiosko-Fingerprint'),
        ], $ip);
    }

    public function revoke(string $uuid): KioskoDevice
    {
        return DB::transaction(function () use ($uuid) {
            $device = KioskoDevice::where('uuid', $uuid)->firstOrFail();

            $device->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'device_session_token_hash' => null,
                'activation_token_hash' => null,
                'activation_expires_at' => null,
            ]);

            Log::info('Kiosko revocado', ['uuid' => $device->uuid]);

            return $device->fresh(self::WITH);
        });
    }

    public function setActiveStatus(string $uuid, bool $active): KioskoDevice
    {
        $device = KioskoDevice::where('uuid', $uuid)->firstOrFail();

        if ($device->status === 'revoked') {
            throw new LogicException('El dispositivo está revocado. Genera un nuevo link de activación.');
        }

        $device->update(['status' => $active ? 'active' : 'inactive']);

        return $device->fresh(self::WITH);
    }

    public function delete(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $device = KioskoDevice::where('uuid', $uuid)->firstOrFail();

            $device->delete();

            Log::info('Dispositivo kiosko eliminado', ['uuid' => $device->uuid]);
        });
    }

    private function assertSessionIsValid(KioskoDevice $device, string $sessionToken, string $fingerprint, array $fingerprintCandidates = []): void
    {
        if ($device->status !== 'active' || $device->revoked_at) {
            throw new AuthorizationException('El kiosko no está activo.');
        }

        if (!$device->device_session_token_hash || !$device->device_fingerprint_hash) {
            throw new AuthorizationException('El kiosko no ha sido activado.');
        }

        if (!hash_equals($device->device_session_token_hash, $this->hashToken($sessionToken))) {
            throw new AuthorizationException('La sesión del kiosko no es válida.');
        }

        $primaryFingerprintHash = $this->hashToken($fingerprint);

        if (hash_equals($device->device_fingerprint_hash, $primaryFingerprintHash)) {
            return;
        }

        foreach ($fingerprintCandidates as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }

            if (hash_equals($device->device_fingerprint_hash, $this->hashToken($candidate))) {
                $device->forceFill([
                    'device_fingerprint_hash' => $primaryFingerprintHash,
                ])->save();

                Log::info('Huella de kiosko migrada a formato estable', ['uuid' => $device->uuid]);
                return;
            }
        }

        if (!hash_equals($device->device_fingerprint_hash, $primaryFingerprintHash)) {
            throw new AuthorizationException('Este kiosko fue activado en otro dispositivo.');
        }
    }

    private function buildToken(): array
    {
        $plain = Str::random(80);

        return [
            'plain' => $plain,
            'hash' => $this->hashToken($plain),
        ];
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function activationUrl(string $token): string
    {
        return rtrim((string) config('app.frontend_url', env('FRONTEND_URL', '')), '/') . '/kiosko/activar/' . $token;
    }

    private function kioskoUrl(string $uuid): string
    {
        return rtrim((string) config('app.frontend_url', env('FRONTEND_URL', '')), '/') . '/kiosko/' . $uuid;
    }

    private function guestUrl(string $uuid, string $token): string
    {
        return rtrim((string) config('app.frontend_url', env('FRONTEND_URL', '')), '/') . '/kiosko/acceso-temporal/' . $uuid . '/' . $token;
    }
}
