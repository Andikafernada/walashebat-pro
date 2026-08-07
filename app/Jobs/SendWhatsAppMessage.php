<?php

namespace App\Jobs;

use App\Models\AttendanceSession;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Mengirim pesan WhatsApp di luar siklus request.
 *
 * Kegagalan TIDAK dibiarkan senyap: bila sebuah sesi absensi terkait, hasil
 * akhirnya dicatat pada kolom delivery_* sesi tersebut sehingga wali kelas
 * melihat peringatan di dashboard dan bisa mengirim ulang — bukan baru sadar
 * ketika absensi ternyata kosong.
 *
 * Catatan keamanan: isi pesan (termasuk PIN harian) tersimpan sementara di
 * tabel jobs sampai diproses, lalu barisnya terhapus. Basis datanya sama
 * dengan tempat pin_hash berada.
 */
class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60];

    /**
     * @param  int  $userId  Wali kelas pemilik pesan ini. WAJIB, tanpa nilai
     *                       bawaan, dan itu disengaja: masa aktif otomasi
     *                       diperiksa dari sini, jadi setiap jalur pengiriman
     *                       otomatis baru terpaksa menyebutkan pemiliknya dan
     *                       tidak bisa melewati gerbang tanpa sengaja.
     */
    public function __construct(
        public readonly string $to,
        public readonly string $message,
        public readonly int $userId,
        public readonly array $meta = [],
        public readonly ?int $attendanceSessionId = null,
        public readonly ?string $from = null,
    ) {}

    public function handle(NotificationChannel $notifier): void
    {
        if (! $this->otomasiDiizinkan()) {
            return;
        }

        if (! $notifier->send($this->to, $this->message, $this->meta, $this->from)) {
            throw new \RuntimeException('Gateway WhatsApp menolak pesan.');
        }

        $this->markSession([
            'delivery_status' => 'sent',
            'delivery_error' => null,
            'delivered_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[WA] Pesan gagal setelah semua percobaan', [
            'from' => $this->from,
            'to' => $this->to,
            'meta' => $this->meta,
            'error' => $e->getMessage(),
        ]);

        $this->markSession([
            'delivery_status' => 'failed',
            // Dipotong agar muat di kolom dan tidak membocorkan jejak panjang.
            'delivery_error' => mb_substr($e->getMessage(), 0, 200),
        ]);
    }

    /**
     * Gerbang masa aktif otomasi WhatsApp.
     *
     * Diperiksa di sini — di dalam job, bukan di tempat dispatch — karena masa
     * aktif bisa habis setelah pekerjaan mengantre tapi sebelum dijalankan.
     * Memeriksanya hanya saat dispatch akan meloloskan pesan yang sudah tidak
     * berhak kirim.
     *
     * Sengaja TIDAK melempar exception: masa langganan yang habis bukan
     * kegagalan teknis. Melemparnya akan memicu tiga kali percobaan ulang lalu
     * mengotori failed_jobs dengan sesuatu yang tidak akan pernah berhasil.
     */
    private function otomasiDiizinkan(): bool
    {
        $pemilik = User::find($this->userId);

        if ($pemilik === null) {
            // Pengguna terhapus setelah pesan mengantre. Tidak ada yang perlu
            // dilayani, dan tidak ada yang perlu dicoba ulang.
            $this->markSession([
                'delivery_status' => 'skipped',
                'delivery_error' => 'Pemilik pesan sudah tidak ada.',
            ]);

            return false;
        }

        if ($pemilik->otomasiWhatsAppAktif()) {
            return true;
        }

        Log::info('[WA] Otomasi dilewati, masa langganan habis', [
            'user_id' => $pemilik->id,
            'berakhir' => $pemilik->subscription_ends_at?->toDateString(),
            'type' => $this->meta['type'] ?? null,
        ]);

        // Ditandai eksplisit agar wali kelas melihat alasannya di dashboard,
        // bukan menyangka gateway rusak lalu menghubungi dukungan.
        $this->markSession([
            'delivery_status' => 'skipped',
            'delivery_error' => 'Masa otomasi WhatsApp sudah berakhir. Perpanjang langganan untuk mengaktifkan kembali.',
        ]);

        return false;
    }

    private function markSession(array $attributes): void
    {
        if (! $this->attendanceSessionId) {
            return;
        }

        // withoutTenant(): berjalan di worker tanpa user login.
        AttendanceSession::withoutTenant()
            ->whereKey($this->attendanceSessionId)
            ->update($attributes);
    }
}
