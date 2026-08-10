<?php

namespace App\Support\Notifications;

use App\Models\User;
use App\Support\CircuitBreaker;
use App\Support\Contracts\WhatsAppSessionManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mengelola sesi WhatsApp per guru lewat webhook n8n.
 *
 * Gateway diharapkan menyediakan tiga aksi pada satu webhook, dibedakan
 * lewat field "action": pair, status, disconnect. Lihat
 * docs/n8n-whatsapp-workflow.md untuk spesifikasi payload.
 *
 * Dengan circuit breaker: koneksi yang gagal cepat terdeteksi (timeout 3 detik)
 * dan setelah 3 kegagalan berturut-turut, sistem berhenti mencoba selama 60 detik
 * untuk memberi gateway waktu recovery.
 */
class N8nSessionManager implements WhatsAppSessionManager
{
    /**
     * Berapa lama daftar grup dianggap masih segar.
     *
     * WhatsApp membatasi laju groupFetchAllParticipating dengan ketat — dua
     * permintaan berdekatan sudah bisa memicu "rate-overlimit" — sedangkan
     * keanggotaan grup jarang berubah. Menyimpannya sebentar membuat memuat
     * halaman berulang kali tidak lagi menghubungi WhatsApp berulang kali.
     */
    private const GRUP_TTL = 300;

    /**
     * Berapa lama NAMA grup diingat, terlepas dari kesegaran daftarnya.
     *
     * Dipakai untuk menampilkan grup yang sudah dipilih dan disimpan tanpa
     * memindai ulang. Nama grup nyaris tidak pernah berubah, dan bila pun
     * berubah, akibat terburuknya hanyalah label yang usang sampai guru
     * membuka pemilih grup lagi — jauh lebih ringan daripada menghubungi
     * WhatsApp setiap kali halaman dibuka. 30 hari.
     */
    private const NAMA_GRUP_TTL = 2592000;

    private CircuitBreaker $circuit;

    private int $timeout;
    private int $fastTimeout;

    public function __construct(
        private readonly string $webhookUrl,
        private readonly ?string $secret = null,
        int $timeout = 5, // Default 5 detik, tidak 15
    ) {
        $this->circuit = new CircuitBreaker('whatsapp-session', failureThreshold: 3, resetTimeout: 60);
        $this->timeout = $timeout;
        $this->fastTimeout = 3; // Fast fail untuk health check
    }

    /**
     * Cek apakah gateway tersedia.
     */
    public function isHealthy(): bool
    {
        return $this->circuit->isAvailable();
    }

    /**
     * Dapatkan status circuit breaker.
     */
    public function getCircuitStatus(): array
    {
        return $this->circuit->getStatus();
    }

    public function startPairing(User $user): array
    {
        $data = $this->call('pair', $user);

        return [
            'session_id' => $data['session_id'] ?? $this->sessionId($user),
            'qr' => $data['qr'] ?? null,
            'status' => $data['status'] ?? 'pairing',
        ];
    }

    public function status(User $user): array
    {
        $data = $this->call('status', $user);

        return [
            'status' => $data['status'] ?? 'disconnected',
            'qr' => $data['qr'] ?? null,
            'error' => $data['error'] ?? null,
        ];
    }

    public function groups(User $user): array
    {
        return $this->groupsResult($user)['groups'];
    }

    public function groupsResult(User $user, bool $paksaSegar = false): array
    {
        $kunci = 'wa:grup:'.$this->sessionId($user);
        $tersimpan = Cache::get($kunci);

        if (! $paksaSegar && is_array($tersimpan)) {
            return ['ok' => true, 'groups' => $tersimpan, 'cached' => true, 'error' => null];
        }

        $data = $this->call('groups', $user);

        /*
         * Gateway hanya mengirim kunci "groups" saat pengambilan benar-benar
         * berhasil; jalur gagal di call() membalas ['status' => ..., 'error' => ...].
         * Membedakan keduanya penting: daftar kosong dari gateway berarti guru
         * memang belum masuk grup mana pun, sedangkan kegagalan berarti kita
         * tidak tahu apa-apa dan tidak boleh ditampilkan seolah "tidak ada grup".
         */
        if (! is_array($data['groups'] ?? null)) {
            $pesan = $data['error'] ?? 'Daftar grup WhatsApp gagal diambil.';

            /*
             * "ok" berarti ada daftar yang layak ditampilkan, bukan berarti
             * percobaan barusan mulus. Kalau muat ulang gagal padahal cache
             * masih ada, daftar lama tetap disajikan — menghapusnya justru
             * merampas data yang sedang dilihat guru — dan kegagalannya
             * dilaporkan lewat "error" agar bisa ditampilkan sebagai catatan.
             */
            if (is_array($tersimpan)) {
                return ['ok' => true, 'groups' => $tersimpan, 'cached' => true, 'error' => $pesan];
            }

            return ['ok' => false, 'groups' => [], 'cached' => false, 'error' => $pesan];
        }

        // Hanya hasil yang berhasil yang disimpan; kegagalan jangan sampai
        // ikut membeku selama TTL.
        Cache::put($kunci, $data['groups'], self::GRUP_TTL);
        $this->ingatNamaGrup($user, $data['groups']);

        return ['ok' => true, 'groups' => $data['groups'], 'cached' => false, 'error' => null];
    }

    public function groupLabels(User $user): array
    {
        $peta = Cache::get('wa:grupnama:'.$this->sessionId($user));

        return is_array($peta) ? $peta : [];
    }

    /**
     * Simpan nama grup yang barusan terlihat, untuk ditampilkan tanpa memindai.
     *
     * Digabung, bukan ditimpa. Sekali pindai hanya melaporkan grup yang masih
     * diikuti guru saat itu; menimpanya akan melenyapkan nama grup yang sedang
     * dipakai tetapi kebetulan tidak terbawa pada pengambilan ini, dan
     * pilihan yang tersimpan berubah menjadi deretan JID mentah di layar.
     *
     * TTL-nya jauh lebih panjang dari GRUP_TTL karena tugasnya berbeda:
     * GRUP_TTL menjaga daftar pilihan tetap segar, peta ini hanya perlu
     * mengingat nama sesuatu yang sudah dipilih.
     *
     * @param  array<int, array{id?: string, subject?: string, peserta?: int}>  $grup
     */
    private function ingatNamaGrup(User $user, array $grup): void
    {
        $kunci = 'wa:grupnama:'.$this->sessionId($user);
        $peta = Cache::get($kunci);
        $peta = is_array($peta) ? $peta : [];

        foreach ($grup as $g) {
            $id = $g['id'] ?? null;

            if (! is_string($id) || $id === '') {
                continue;
            }

            $peta[$id] = [
                'subject' => (string) ($g['subject'] ?? $id),
                'peserta' => (int) ($g['peserta'] ?? 0),
            ];
        }

        Cache::put($kunci, $peta, self::NAMA_GRUP_TTL);
    }

    public function autoreplyStatus(User $user): array
    {
        $data = $this->call('autoreply-status', $user);

        return [
            // Bawaan MATI. Kalau gateway tidak menjawab, jangan sampai UI
            // menampilkan "aktif" untuk sesuatu yang belum tentu berjalan.
            'enabled' => (bool) ($data['enabled'] ?? false),
            'groups' => is_array($data['groups'] ?? null) ? $data['groups'] : [],
            'jam' => $data['jam'] ?? null,
            /*
             * Membedakan "benar-benar mati" dari "tidak tahu".
             *
             * Saat gateway tak terjangkau, call() membalas tanpa 'enabled' dan
             * 'groups', sehingga bawaan di atas menghasilkan mati + tanpa grup.
             * Tanpa penanda ini halaman menyajikannya sebagai fakta: wali kelas
             * melihat balasan otomatisnya mati dan seluruh pilihan grupnya
             * lenyap, lalu menyangka pengaturannya hilang — padahal utuh, hanya
             * tidak terbaca. Kekeliruan itu mendorongnya menyetel ulang sesuatu
             * yang tidak rusak.
             */
            'error' => $data['error'] ?? null,
        ];
    }

    public function autoreplyCheck(User $user, string $groupId): array
    {
        $data = $this->call('autoreply-check', $user, ['group_id' => $groupId]);

        // Gateway hanya mengirim "syarat" saat pemeriksaan benar-benar jalan;
        // jalur gagal di call() membalas ['status' => ..., 'error' => ...].
        if (! is_array($data['syarat'] ?? null)) {
            return [
                'siap' => false,
                'syarat' => [],
                'jam' => null,
                'kuota_harian' => 0,
                'terpakai_hari_ini' => 0,
                'error' => $data['error'] ?? 'Status balasan otomatis gagal diperiksa.',
            ];
        }

        return [
            'siap' => (bool) ($data['siap'] ?? false),
            'syarat' => $data['syarat'],
            'jam' => $data['jam'] ?? null,
            'kuota_harian' => (int) ($data['kuota_harian'] ?? 0),
            'terpakai_hari_ini' => (int) ($data['terpakai_hari_ini'] ?? 0),
            'error' => null,
        ];
    }

    public function autoreplySave(User $user, bool $enabled, array $groups, array $permissionKeywords = [], array $sickKeywords = [], array $students = [], array $ragam = []): bool
    {
        $data = $this->call('autoreply-set', $user, [
            'enabled' => $enabled,
            'groups' => array_values($groups),
            'permission_keywords' => array_values($permissionKeywords),
            'sick_keywords' => array_values($sickKeywords),
            /*
             * TIDAK memakai array_values(): kuncinya adalah nomor orang tua,
             * dan justru kunci itulah yang dipakai gateway untuk mencari nama
             * anak. array_values() akan membuangnya dan menyisakan daftar nama
             * tanpa pemilik.
             */
            'students' => (object) $students,
            // Variasi kalimat milik wali kelas, per kategori. Menambah ragam
            // bawaan gateway, bukan menggantikannya.
            'ragam' => (object) array_filter($ragam, fn ($v) => is_array($v) && $v !== []),
        ]);

        return ($data['ok'] ?? false) === true;
    }

    public function disconnect(User $user): bool
    {
        // Nomor yang ditautkan bisa berganti setelah ini, jadi daftar grup lama
        // tidak boleh bertahan sampai TTL habis.
        Cache::forget('wa:grup:'.$this->sessionId($user));

        return ($this->call('disconnect', $user)['ok'] ?? false) === true;
    }

    /** Identitas sesi stabil per guru, tidak memakai nomor agar bisa berganti. */
    private function sessionId(User $user): string
    {
        return 'guru-'.$user->id;
    }

    /**
     * Eksekusi request ke gateway dengan circuit breaker.
     *
     * @return array<string, mixed>
     */
    private function call(string $action, User $user, array $tambahan = []): array
    {
        // Fast fail jika circuit sudah open
        if (! $this->circuit->isAvailable()) {
            Log::warning('[WA:sesi] Circuit breaker OPEN - skip request', [
                'action' => $action,
                'user_id' => $user->id,
            ]);
            return ['status' => 'disconnected', 'error' => 'Gateway sedang tidak tersedia.'];
        }

        try {
            $timeout = ($action === 'pair') ? 25 : $this->fastTimeout;
            $response = Http::timeout($timeout)
                ->connectTimeout(2)
                ->withHeaders(array_filter(['X-Webhook-Secret' => $this->secret]))
                ->post($this->webhookUrl, $tambahan + [
                    'action' => $action,
                    'session_id' => $this->sessionId($user),
                    'msisdn' => $user->whatsapp_number,
                ]);

            if ($response->failed()) {
                $this->circuit->recordFailure();
                Log::warning('[WA:sesi] Gateway menolak', [
                    'action' => $action,
                    'status' => $response->status(),
                    'user_id' => $user->id,
                    'circuit_failures' => $this->circuit->getStatus()['failures'],
                ]);

                return ['status' => 'disconnected', 'error' => 'Gateway tidak merespons dengan benar.'];
            }

            $this->circuit->recordSuccess();
            return $response->json() ?? [];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Timeout, connection refused, dll
            $this->circuit->recordFailure();
            Log::warning('[WA:sesi] Koneksi gagal (fast fail)', [
                'action' => $action,
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'circuit_failures' => $this->circuit->getStatus()['failures'],
            ]);

            return ['status' => 'disconnected', 'error' => 'Tidak dapat terhubung ke gateway WhatsApp.'];

        } catch (\Throwable $e) {
            $this->circuit->recordFailure();
            Log::error('[WA:sesi] Exception', [
                'action' => $action,
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return ['status' => 'disconnected', 'error' => 'Gateway tidak dapat dihubungi.'];
        }
    }
}
