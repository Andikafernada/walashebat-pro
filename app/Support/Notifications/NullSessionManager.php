<?php

namespace App\Support\Notifications;

use App\Models\User;
use App\Support\Contracts\WhatsAppSessionManager;

/**
 * Mode pengembangan: menganggap sesi selalu tersambung tanpa gateway sungguhan,
 * sehingga alur absensi bisa dicoba di localhost.
 */
class NullSessionManager implements WhatsAppSessionManager
{
    public function startPairing(User $user, string $metode = self::METODE_QR): array
    {
        return [
            'session_id' => 'dev-'.$user->id,
            'qr' => null,
            'pairing_code' => null,
            'metode' => $metode,
            'status' => 'connected',
        ];
    }

    public function status(User $user): array
    {
        return ['status' => 'connected', 'qr' => null, 'pairing_code' => null, 'error' => null];
    }

    public function groups(User $user): array
    {
        return [];
    }

    public function groupsResult(User $user, bool $paksaSegar = false): array
    {
        return ['ok' => true, 'groups' => [], 'cached' => false, 'error' => null];
    }

    public function groupLabels(User $user): array
    {
        return [];
    }

    public function autoreplyStatus(User $user): array
    {
        return [
            'enabled' => false,
            'groups' => [],
            'jam' => null,
            'error' => 'Gateway WhatsApp tidak aktif pada pemasangan ini.',
        ];
    }

    public function autoreplyCheck(User $user, string $groupId): array
    {
        return [
            'siap' => false,
            'syarat' => [],
            'jam' => null,
            'kuota_harian' => 0,
            'terpakai_hari_ini' => 0,
            'error' => 'Gateway WhatsApp tidak aktif pada pemasangan ini.',
        ];
    }

    public function autoreplySave(User $user, bool $enabled, array $groups, array $permissionKeywords = [], array $sickKeywords = [], array $students = [], array $ragam = [], array $links = []): bool
    {
        return false;
    }

    public function disconnect(User $user): bool
    {
        return true;
    }

    /**
     * Null mode selalu sehat (tidak ada gateway yang bisa down).
     */
    public function isHealthy(): bool
    {
        return true;
    }

    /**
     * Null mode tidak punya circuit breaker.
     */
    public function getCircuitStatus(): array
    {
        return [
            'name' => 'null-session',
            'state' => 'closed',
            'failures' => 0,
            'threshold' => 0,
            'time_until_retry' => 0,
        ];
    }
}
