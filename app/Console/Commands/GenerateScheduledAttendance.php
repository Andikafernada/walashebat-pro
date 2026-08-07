<?php

namespace App\Console\Commands;

use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Services\AttendanceSessionService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Membuat sesi absensi otomatis — SATU per kelas per hari — lalu mengirim
 * magic link + PIN DARI nomor WhatsApp wali kelas KE petugas Seksi Absensi.
 *
 * Satu pesan per hari, berapa pun jumlah mata pelajaran hari itu.
 *
 * Penentuan waktu:
 * Jamnya TIDAK tetap. Sistem mengambil jadwal pelajaran paling awal pada hari
 * itu untuk kelas bersangkutan, lalu mengirim tautan beberapa menit sebelum
 * jam mulainya. Dengan begitu sekolah bersistem blok — yang jam pertamanya
 * berbeda-beda tiap hari — tetap terlayani, dan hari tanpa jadwal otomatis
 * terlewati tanpa perlu aturan khusus.
 *
 * Dijalankan tiap menit oleh scheduler dan aman diulang: sesi otomatis selalu
 * memakai sequence = 1, dan unique (class_id, session_date, sequence) mencegah
 * duplikat di level database. Sesi tambahan yang dibuat manual oleh wali kelas
 * memakai urutan 2 ke atas sehingga tidak bertabrakan dengan sesi otomatis.
 */
class GenerateScheduledAttendance extends Command
{
    protected $signature = 'walikelas:generate-attendance
                            {--lead=10 : Kirim tautan berapa menit sebelum jam pelajaran pertama}
                            {--dry-run : Tampilkan saja, jangan buat/kirim}';

    protected $description = 'Buat sesi absensi harian dari jadwal & kirim magic link ke Seksi Absensi';

    public function handle(AttendanceSessionService $service): int
    {
        $now = now();
        $lead = max(0, (int) $this->option('lead'));
        $dry = (bool) $this->option('dry-run');

        // ISO-8601: 1=Senin .. 7=Minggu, sama dengan Schedule::DAYS.
        $today = $now->dayOfWeekIso;

        /*
         * PENYARINGAN DILAKUKAN DI SQL, BUKAN DI PHP.
         *
         * Perintah ini berjalan tiap menit selamanya. Memuat seluruh kelas
         * ber-auto-absen lalu menyaring per menit di PHP akan menjadi puluhan
         * ribu query per menit begitu penggunanya ribuan. Karena itu yang
         * diambil hanya jadwal yang jam mulainya benar-benar jatuh di jendela
         * kirim saat ini — pada hampir semua menit, hasilnya kosong dan
         * perintah selesai tanpa kerja tambahan.
         *
         * Jendela: start_time berada di [sekarang - 1 menit, sekarang + lead].
         * Diasumsikan jam pelajaran tidak melewati tengah malam.
         */
        $windowStart = $now->copy()->subMinute();
        $windowEnd = $now->copy()->addMinutes($lead);

        if ($windowStart->toDateString() !== $windowEnd->toDateString()) {
            return self::SUCCESS; // jendela melewati tengah malam; abaikan
        }

        $candidates = Schedule::withoutTenant()
            ->where('day_of_week', $today)
            ->whereBetween('start_time', [
                $windowStart->format('H:i:s'),
                $windowEnd->format('H:i:s'),
            ])
            ->whereIn('class_id', Classroom::withoutTenant()
                ->where('auto_attendance', true)
                ->where('is_active', true)
                /*
                 * Kelas ajar TIDAK PERNAH ikut absensi otomatis.
                 *
                 * Magic link + PIN dikirim ke Seksi Absensi lewat grup atau
                 * nomor kelas. Pada kelas ajar, grup itu milik wali kelas LAIN
                 * — mengirimnya berarti menaruh tautan absensi beserta PIN di
                 * percakapan yang bukan milik pengirim.
                 *
                 * Model sudah memaksa auto_attendance mati untuk kelas ajar;
                 * penyaring ini lapis kedua, supaya baris lama yang terlanjur
                 * bernilai true pun tidak lolos.
                 */
                ->where('jenis', '!=', Classroom::JENIS_AJAR)
                ->select('id'))
            ->get();

        /*
         * SEMUA PENCARIAN PENDUKUNG DIAMBIL SEKALIGUS, BUKAN PER KELAS.
         *
         * Perulangan di bawah dulu menjalankan lima query untuk setiap kelas.
         * Pada satu kelas itu tidak terasa, tetapi jam pertama sekolah di
         * Indonesia hampir seragam pukul 07:00 — artinya seluruh kelas menjadi
         * kandidat pada menit yang sama. Pada 6.000 kelas itu berarti sekitar
         * 42.000 query dalam satu jalannya perintah, di dalam perintah yang
         * dijadwalkan tiap menit. Terukur: 59 detik dari jatah 60 detik.
         *
         * Dikumpulkan lebih dulu, jumlah query berhenti tumbuh mengikuti
         * jumlah kelas.
         */
        $classIds = $candidates->pluck('class_id')->unique()->all();
        $tanggalIni = $now->toDateString();

        // Jam pelajaran paling awal hari ini per kelas — penentu "pelajaran
        // pertama". Menggantikan query $hasEarlier per kelas.
        $jamPertama = Schedule::withoutTenant()
            ->where('day_of_week', $today)
            ->whereIn('class_id', $classIds)
            ->groupBy('class_id')
            ->selectRaw('class_id, MIN(start_time) as paling_awal, MAX(end_time) as paling_akhir')
            ->get()
            ->keyBy('class_id');

        // Kelas yang sesi otomatisnya (sequence 1) sudah ada hari ini.
        $sudahPunyaSesi = AttendanceSession::withoutTenant()
            ->whereIn('class_id', $classIds)
            ->whereDate('session_date', $tanggalIni)
            ->where('sequence', 1)
            ->pluck('class_id')
            ->flip();

        $kelasTerkumpul = Classroom::withoutTenant()
            ->with(['owner', 'organizationStructures.student'])
            ->whereIn('id', $classIds)
            ->get()
            ->keyBy('id');

        // Libur diambil sekali, lalu dicocokkan di memori. Barisnya sedikit
        // (libur nasional + libur per tenant) sehingga jauh lebih murah
        // daripada satu query per kelas.
        $pemilikIds = $kelasTerkumpul->pluck('user_id')->unique()->all();
        $liburHariIni = Holiday::query()
            ->whereDate('start_date', '<=', $tanggalIni)
            ->whereDate('end_date', '>=', $tanggalIni)
            ->where(fn ($w) => $w->whereNull('user_id')->orWhereIn('user_id', $pemilikIds))
            ->get(['user_id']);

        $liburNasional = $liburHariIni->contains(fn ($l) => $l->user_id === null);
        $liburTenant = $liburHariIni->pluck('user_id')->filter()->flip();

        foreach ($candidates as $first) {
            $ringkasanJam = $jamPertama->get($first->class_id);

            /*
             * Hanya pelajaran PERTAMA hari itu yang menjadi penanda absensi.
             * Kelas dengan lima mapel tetap menghasilkan satu sesi saja.
             */
            if ($ringkasanJam && $ringkasanJam->paling_awal < $first->start_time) {
                continue;
            }

            // Sudah ada sesi OTOMATIS untuk kelas ini hari ini?
            // Sengaja hanya melihat sequence 1: sesi tambahan buatan wali kelas
            // tidak boleh membatalkan sesi otomatis besok paginya.
            if ($sudahPunyaSesi->has($first->class_id)) {
                continue;
            }

            $classroom = $kelasTerkumpul->get($first->class_id);

            if (! $classroom) {
                continue;
            }

            // Lewati bila hari ini libur (nasional atau libur khusus tenant).
            if ($liburNasional || $liburTenant->has($classroom->user_id)) {
                continue;
            }

            [$target, $sender, $problem] = $this->resolveNumbers($classroom);

            if ($dry) {
                $this->line(sprintf(
                    '[dry-run] %s | jam pertama %s (%s) | dari %s -> ke %s%s',
                    $classroom->name,
                    substr($first->start_time, 0, 5),
                    $first->subject,
                    $sender ?: '-',
                    $target ?: '-',
                    $problem ? ' | MASALAH: '.$problem : ''
                ));

                continue;
            }

            try {
                // Sesi berlaku sampai pelajaran terakhir hari itu selesai.
                // Diambil dari ringkasan yang sudah dikumpulkan di atas.
                $lastEnd = $ringkasanJam?->paling_akhir;

                $endsAt = Carbon::parse($lastEnd ?? $first->end_time, config('app.timezone'))
                    ->setDateFrom($now);

                $ttl = max(15, (int) $now->diffInMinutes($endsAt, false));

                ['session' => $session, 'pin' => $pin] = $service->create(
                    classroom: $classroom,
                    schedule: $first,
                    title: 'Absensi '.$now->translatedFormat('l, d M Y'),
                    ttlMinutes: $ttl,
                    // sequence: 1 DIKUNCI. Sesi otomatis harus selalu menempati
                    // urutan pertama supaya unique(class_id, session_date,
                    // sequence) tetap menghentikan duplikat bila dua proses
                    // scheduler berjalan bersamaan.
                    sequence: 1,
                );
            } catch (QueryException $e) {
                /*
                 * Hanya tabrakan unique(class_id, session_date) yang wajar
                 * dilewati. Galat lain WAJIB terdengar; menelan semuanya
                 * membuat kegagalan absensi tak terlihat sama sekali.
                 */
                $sudahAda = AttendanceSession::withoutTenant()
                    ->where('class_id', $first->class_id)
                    ->whereDate('session_date', $now->toDateString())
                    ->where('sequence', 1)
                    ->exists();

                if ($sudahAda) {
                    continue;
                }

                Log::error('[absensi] Gagal membuat sesi otomatis', [
                    'class_id' => $first->class_id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Gagal membuat sesi untuk kelas {$first->class_id}: {$e->getMessage()}");

                continue;
            }

            if ($problem) {
                // Tidak senyap: sesi tetap dibuat (wali kelas masih bisa
                // membuka tautannya sendiri), tapi ditandai gagal agar muncul
                // sebagai peringatan di dashboard.
                $session->update([
                    'delivery_status' => 'failed',
                    'delivery_error' => $problem,
                ]);

                $this->warn("{$classroom->name}: {$problem}");

                continue;
            }

            $service->dispatchMagicLink($session, $target, $pin, $sender);

            $this->info("Diantrikan -> {$classroom->name} | {$sender} -> {$target}");
        }

        return self::SUCCESS;
    }

    /**
     * Tentukan nomor pengirim dan penerima.
     *
     * Pengirim : SELALU nomor WhatsApp akun wali kelas — itulah satu-satunya
     *            nomor yang benar-benar ditautkan ke gateway. Memakai nomor
     *            lain mustahil, karena sesi pengirim terikat pada nomor yang
     *            dipindai QR-nya oleh guru.
     * Penerima : Seksi Absensi -> Ketua Kelas -> nomor cadangan kelas.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string} [penerima, pengirim, masalah]
     */
    private function resolveNumbers(Classroom $classroom): array
    {
        $owner = $classroom->owner;
        $sender = $owner?->whatsapp_number;

        $recipient = null;
        foreach (['seksi_absensi', 'ketua'] as $role) {
            $student = $classroom->organizationStructures
                ->firstWhere('role', $role)?->student;

            if ($student?->phone) {
                $recipient = $student->phone;
                break;
            }
        }

        $recipient ??= $classroom->homeroom_wa ?: null;

        // Pesan galat dibuat spesifik supaya wali kelas tahu persis apa
        // yang harus diperbaiki, bukan sekadar "pengiriman gagal".
        $problem = match (true) {
            ! $sender => 'Nomor WhatsApp Anda belum diisi di profil akun.',
            ! $owner?->whatsappConnected() => 'Nomor WhatsApp Anda belum tersambung. Buka menu Nomor WhatsApp lalu pindai QR.',
            ! $recipient => 'Belum ada Seksi Absensi atau Ketua Kelas yang punya nomor HP. Lengkapi di menu Struktur.',
            default => null,
        };

        return [$recipient, $sender, $problem];
    }
}
