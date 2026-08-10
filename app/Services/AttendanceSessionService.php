<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\Scopes\TenantScope;
use App\Models\Classroom;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Logika inti sistem absensi anti-curang.
 *
 * - Membuat sesi dengan token acak + PIN harian (di-hash) + waktu kedaluwarsa.
 * - Membangun magic link dan mengantrikan pengirimannya via WhatsApp.
 * - PIN hanya ada di memori sesaat; yang tersimpan adalah hash-nya.
 */
class AttendanceSessionService
{
    /** Hitung ringkasan statistik kehadiran sesi presensi. */
    public function summary(AttendanceSession $session): array
    {
        $session->loadMissing('attendances');

        $total = $session->attendances->count();
        $hadir = $session->attendances->where('status', 'hadir')->count();
        $sakit = $session->attendances->where('status', 'sakit')->count();
        $izin  = $session->attendances->where('status', 'izin')->count();
        $alfa  = $session->attendances->where('status', 'alfa')->count();

        return [
            'total' => $total,
            'hadir' => $hadir,
            'sakit' => $sakit,
            'izin'  => $izin,
            'alfa'  => $alfa,
            'persentase_hadir' => $total > 0 ? round(($hadir / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Buat sesi absensi baru untuk sebuah kelas.
     *
     * $sequence menentukan urutan sesi pada hari itu dan ikut menjadi kunci
     * unik di database. Scheduler WAJIB mengirim 1 supaya sesi otomatis tidak
     * pernah kembar; pembuatan manual membiarkannya null agar terisi otomatis
     * dengan urutan berikutnya.
     *
     * @return array{session: AttendanceSession, pin: string}  PIN plaintext untuk sekali tampil.
     */
    public function create(
        Classroom $classroom,
        ?Schedule $schedule = null,
        ?string $title = null,
        ?int $ttlMinutes = null,
        ?Carbon $date = null,
        ?int $sequence = null,
    ): array {
        $pin = $this->generatePin();
        $ttlMinutes ??= (int) config('walikelas.session_ttl_minutes');
        $sessionDate = $date ?? now();
        $sequence ??= AttendanceSession::nextSequenceFor($classroom->id, $sessionDate);

        $session = new AttendanceSession([
            /*
             * user_id DIISI EKSPLISIT, tidak mengandalkan auto-isi dari
             * BelongsToTenant. Auto-isi itu hanya bekerja bila ada user login,
             * sedangkan scheduler dan queue worker berjalan tanpa auth.
             */
            'user_id' => $classroom->user_id,
            'class_id' => $classroom->id,
            'schedule_id' => $schedule?->id,
            'title' => $title ?? ('Absensi '.now()->translatedFormat('l, d M Y')),
            'session_date' => $sessionDate->toDateString(),
            'sequence' => $sequence,
            'token' => $this->generateToken(),
            'pin_hash' => $this->hashPin($pin),
            'expires_at' => now()->addMinutes($ttlMinutes),
            'status' => 'open',
        ]);
        $session->save();

        return ['session' => $session, 'pin' => $pin];
    }

    /**
     * Antrikan pengiriman magic link + PIN ke petugas via WhatsApp.
     *
     * Mengembalikan true berarti pesan BERHASIL DIANTRIKAN, bukan berhasil
     * sampai ke penerima. Hasil akhir pengiriman ada di log/queue worker.
     */
    public function dispatchMagicLink(
        AttendanceSession $session,
        string $toNumber,
        string $pin,
        ?string $fromNumber = null,
    ): bool {
        /*
         * Sesi ini sah dipegang, jadi tenantnya diketahui: pemiliknya sendiri.
         *
         * Dipanggil dari cron tanpa siapa pun yang login, dan relasi di bawah
         * ($session->classroom, ->owner) membawa TenantScope yang gagal-tertutup
         * — tanpa baris ini kelasnya terbaca null dan pesan absensi terkirim
         * tanpa nama kelas, atau gagal sama sekali. Menetapkannya di sini
         * membuat jalur ini benar baik dari cron maupun dari layar guru.
         */
        TenantScope::pakaiTenant($session->user_id);

        $session->loadMissing('classroom');

        $user = $session->classroom?->owner ?? auth()->user();
        $template = $user?->wa_magic_link_template;

        if (! empty($template)) {
            $message = str_replace(
                ['{nama_kelas}', '{magic_link}', '{pin}', '{jam_berlaku}'],
                [$session->classroom->name, $session->magicLink(), $pin, $session->expires_at->format('H:i')],
                $template
            );
        } else {
            $message = implode("\n", [
                '*Wali Kelas Hebat - Absensi Kelas '.$session->classroom->name.'*',
                '',
                'Petugas absensi, silakan isi kehadiran hari ini melalui tautan berikut:',
                $session->magicLink(),
                '',
                'PIN Harian: *'.$pin.'*',
                'Berlaku s/d: '.$session->expires_at->format('H:i').' WIB',
                '',
                'Jangan bagikan PIN ini ke siapa pun.',
            ]);
        }

        // Tandai "pending" lebih dulu supaya UI bisa membedakan
        // "belum diproses" dari "tidak pernah dikirim".
        $session->update([
            'delivery_status' => 'pending',
            'delivery_target' => $toNumber,
            'delivery_error' => null,
            'delivered_at' => null,
        ]);

        SendWhatsAppMessage::dispatch($toNumber, $message, $session->user_id, [
            'type' => 'attendance_magic_link',
            'session_id' => $session->id,
            'class_id' => $session->class_id,
        ], $session->id, $fromNumber);

        return true;
    }

    /**
     * Kirim rekap kehadiran ke grup WhatsApp orang tua.
     *
     * Satu pesan per kelas per hari — sengaja, karena mengirim ke tiap orang
     * tua satu per satu adalah pola tercepat membuat nomor guru diblokir.
     * Isinya hanya siswa yang TIDAK hadir plus totalnya.
     */
    public function dispatchParentRecap(AttendanceSession $session): bool
    {
        TenantScope::pakaiTenant($session->user_id);

        $session->loadMissing(['classroom.owner', 'attendances.student']);

        $classroom = $session->classroom;
        $grup = $classroom?->parent_group_wa;
        $pengirim = $classroom?->owner?->whatsapp_number;

        if (blank($grup) || blank($pengirim)) {
            return false;
        }

        $jumlah = array_fill_keys(\App\Models\Attendance::STATUSES, 0);
        $tidakHadir = [];

        foreach ($session->attendances as $a) {
            if (isset($jumlah[$a->status])) {
                $jumlah[$a->status]++;
            }

            /*
             * Terlambat IKUT disebut namanya di rekap grup orang tua.
             * Justru inilah informasi yang paling berguna bagi orang tua dan
             * paling sulit mereka ketahui sendiri — anaknya berangkat pagi,
             * tapi sampai di sekolah terlambat.
             */
            if ($a->status !== 'hadir') {
                $tidakHadir[] = '• '.($a->student->name ?? '—').' — '.ucfirst($a->status);
            }
        }

        $baris = [
            '*Rekap Absensi '.$classroom->name.'*',
            $session->session_date->translatedFormat('l, d F Y'),
            '',
            sprintf(
                'Hadir %d · Terlambat %d · Sakit %d · Izin %d · Alfa %d',
                $jumlah['hadir'], $jumlah['terlambat'],
                $jumlah['sakit'], $jumlah['izin'], $jumlah['alfa']
            ),
            '',
        ];

        if ($tidakHadir) {
            $baris[] = 'Tidak hadir:';
            $baris = array_merge($baris, $tidakHadir);
        } else {
            $baris[] = 'Seluruh siswa hadir hari ini.';
        }

        SendWhatsAppMessage::dispatch($grup, implode("\n", $baris), $session->user_id, [
            'type' => 'parent_recap',
            'session_id' => $session->id,
            'class_id' => $session->class_id,
        ], null, $pengirim);

        return true;
    }

    /**
     * Terbitkan PIN baru untuk sesi yang sudah ada.
     *
     * Diperlukan saat kirim ulang: PIN lama hanya tersimpan sebagai hash,
     * jadi pesan yang sama mustahil dikirim ulang. Menerbitkan PIN baru juga
     * lebih aman — PIN pada pesan yang gagal terkirim otomatis tidak berlaku.
     */
    public function regeneratePin(AttendanceSession $session): string
    {
        $pin = $this->generatePin();

        $session->update(['pin_hash' => $this->hashPin($pin)]);

        return $pin;
    }

    /**
     * Hash PIN harian — sengaja lebih murah daripada hash kata sandi.
     *
     * bcrypt bawaan aplikasi memakai cost 12, sekitar 290 ms sekali panggil.
     * Itu tepat untuk kata sandi yang dipakai bertahun-tahun, tetapi keliru di
     * sini: perintah penjadwal membuat sesi untuk SETIAP kelas yang jam
     * pertamanya jatuh pada menit yang sama. Pada 6.000 kelas, hash saja
     * memakan 29 MENIT CPU satu utas — di dalam perintah yang dijadwalkan tiap
     * menit dengan kunci anti-tumpang-tindih 5 menit. Pengamannya justru
     * merusak fitur yang dikawalnya: magic link pagi datang sangat terlambat
     * atau tidak sama sekali.
     *
     * Menurunkannya aman karena yang menjaga PIN ini bukan biaya hash:
     *  - ruang tebakannya hanya 6 digit (10^6) — cost setinggi apa pun tidak
     *    membuat penebakan luring menjadi mustahil;
     *  - penebakan daring dibatasi 10 percobaan per sesi lalu dikunci 10 menit
     *    (walikelas.pin_max_attempts), ditambah throttle rute 30/menit;
     *  - PIN kedaluwarsa bersama sesinya, umumnya dalam 90 menit, dan hanya
     *    berlaku untuk satu sesi itu saja.
     *
     * Hash::check() membaca cost dari hash-nya sendiri, jadi PIN lama yang
     * dibuat dengan cost 12 tetap bisa diverifikasi tanpa migrasi apa pun.
     */
    private function hashPin(string $pin): string
    {
        return Hash::driver('bcrypt')->make($pin, [
            'rounds' => max(4, (int) config('walikelas.pin_hash_rounds', 6)),
        ]);
    }

    private function generatePin(): string
    {
        $length = max(4, (int) config('walikelas.pin_length', 6));
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function generateToken(): string
    {
        // Entropi tinggi, URL-safe, dijamin unik terhadap tabel.
        do {
            $token = Str::lower(Str::random(48));
        } while (AttendanceSession::withoutTenant()->where('token', $token)->exists());

        return $token;
    }
}
