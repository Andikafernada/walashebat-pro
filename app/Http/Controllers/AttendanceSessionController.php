<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceRevision;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Services\AttendanceSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceSessionController extends Controller
{
    /**
     * Penanda `from_status` untuk siswa yang dilewatkan petugas: barisnya baru
     * dibuat wali kelas, jadi tidak ada status sebelumnya. Dipakai penanda,
     * bukan NULL, supaya jejak audit selalu terbaca sebagai "dari → ke".
     */
    private const TANPA_STATUS_AWAL = '-';

    /** Batalkan / Tutup sesi absensi. */
    public function cancel(Classroom $class, AttendanceSession $attendanceSession): RedirectResponse
    {
        abort_if($attendanceSession->class_id !== $class->id, 404);

        /*
         * Hanya sesi yang masih terbuka yang boleh dibatalkan.
         *
         * Tanpa penjagaan ini, sesi yang sudah TERISI pun bisa dibatalkan — dan
         * akibatnya jauh lebih besar daripada kelihatannya: seluruh laporan
         * menyaring `status != 'cancelled'` (rekap kehadiran, analitik, ekspor,
         * profil siswa), sehingga satu klik keliru melenyapkan absensi sehari
         * penuh dari setiap rekap. Datanya masih ada di basis data, jadi tidak
         * ada galat dan tidak ada yang menyadarinya sampai ada yang bertanya
         * ke mana perginya kehadiran hari itu.
         *
         * Aturan ini dulu tertulis di AttendanceSessionPolicy, yang tidak pernah
         * dipanggil dari mana pun — karena itu dipindahkan ke sini.
         */
        if ($attendanceSession->status !== 'open') {
            return redirect()
                ->route('classes.attendance.show', [$class, $attendanceSession])
                ->with('warning', 'Sesi ini sudah tidak terbuka, jadi tidak bisa dibatalkan. Absensi yang sudah masuk tetap tersimpan.');
        }

        $attendanceSession->update([
            'status' => 'cancelled',
            'expires_at' => now(),
        ]);

        return redirect()->route('classes.attendance.show', [$class, $attendanceSession])
            ->with('success', 'Sesi absensi berhasil dibatalkan.');
    }

    /** Kirim ulang magic link / PIN harian ke WhatsApp. */
    public function resend(Request $request, Classroom $class, AttendanceSession $attendanceSession): RedirectResponse
    {
        abort_if($attendanceSession->class_id !== $class->id, 404);

        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $attendanceSession->update([
            'pin_hash' => \Illuminate\Support\Facades\Hash::make($pin),
            'expires_at' => now()->addMinutes(120),
            'status' => 'open',
        ]);

        $sender = $class->owner?->whatsapp_number;
        $target = $this->bolehKirimWa($class)
            ? ($request->input('target_number') ?? $this->defaultRecipient($class))
            : null;

        $sent = false;
        if ($target) {
            $sent = $this->service->dispatchMagicLink($attendanceSession, $target, $pin, $sender);
        }

        return redirect()->route('classes.attendance.show', [$class, $attendanceSession])
            ->with('success', 'PIN harian baru terbit.' . ($sent ? ' Magic link terkirim ke WhatsApp.' : ''))
            ->with('daily_pin', $pin)
            ->with('wa_target', $target);
    }

    public function __construct(private readonly AttendanceSessionService $service) {}

    /**
     * Bolehkah magic link + PIN kelas ini dikirim lewat WhatsApp?
     *
     * Tidak untuk kelas ajar. Tujuan pengirimannya adalah Seksi Absensi lewat
     * grup atau nomor kelas, dan pada kelas ajar keduanya milik WALI KELAS
     * LAIN — mengirimkannya berarti menaruh tautan absensi beserta PIN-nya di
     * percakapan yang bukan milik pengirim. Guru mapel mengabsen sendiri di
     * jam pelajarannya lewat Input Absensi Manual, jadi tidak ada yang hilang.
     *
     * Alasannya sama persis dengan Classroom::bolehAbsensiOtomatis(), yang
     * sudah menutup jalur penjadwal. Yang ini menutup jalur satunya: tombol
     * "Buat & Terbitkan Link" di tab Absensi, tempat centang "Kirim Otomatis
     * via WhatsApp" bahkan MENYALA secara bawaan. Formulirnya kini tidak
     * dirender untuk kelas ajar, tetapi penjagaan tampilan saja tidak cukup:
     * permintaan yang dikirim langsung tetap harus ditolak.
     */
    private function bolehKirimWa(Classroom $class): bool
    {
        return $class->bolehAbsensiOtomatis();
    }

    /** Daftar sesi absensi milik sebuah kelas. */
    public function index(Classroom $class): View
    {
        $sessions = $class->attendanceSessions()
            ->withCount([
                'attendances as hadir_count' => fn ($q) => $q->where('status', 'hadir'),
                'attendances as terisi_count',
            ])
            ->latest('session_date')->latest('sequence')->latest('id')->paginate(20);

        $max = (int) config('walikelas.max_sessions_per_day', 3);

        /*
         * Kesehatan pengiriman WA 30 hari terakhir — satu query agregat.
         *
         * Sebelumnya status kirim ("WA Terkirim"/"WA Gagal") hanya terlihat
         * per baris, satu sesi pada satu waktu. Wali kelas yang WA-nya diam-
         * diam berhenti terkirim (nomor diblokir, sesi gateway putus) tidak
         * akan tahu polanya kecuali menggulir dan menghitung sendiri. Angka
         * ini menjawabnya di depan.
         *
         * Dilewati untuk kelas ajar: magic link WA memang tidak diterbitkan
         * dari sana (lihat catatan di view), jadi "0 dari 0 terkirim" hanya
         * akan terbaca sebagai kabar buruk yang palsu.
         */
        $kesehatanWa = null;
        if (! $class->kelasAjar()) {
            $kesehatanWa = $class->attendanceSessions()
                ->where('session_date', '>=', now()->subDays(30))
                ->selectRaw(<<<'SQL'
                    SUM(CASE WHEN delivery_status = 'sent' THEN 1 ELSE 0 END) as terkirim,
                    SUM(CASE WHEN delivery_status = 'failed' THEN 1 ELSE 0 END) as gagal,
                    COUNT(*) as total
                SQL)->first();
        }

        return view('attendance.index', [
            'classroom' => $class,
            'sessions' => $sessions,
            'totalSiswa' => $class->students()->where('is_active', true)->count(),
            'kuotaHarian' => $max,
            'terpakaiHariIni' => $class->attendanceSessions()->whereDate('session_date', today())->count(),
            'adaSesiTerbuka' => $class->attendanceSessions()
                ->whereDate('session_date', today())
                ->where('status', 'open')
                ->where('expires_at', '>', now())
                ->exists(),
            'kesehatanWa' => $kesehatanWa,
        ]);
    }

    /** Form pengisian absensi manual langsung oleh Wali Kelas (retroaktif/tanggal lampau). */
    public function createManual(Classroom $class): View
    {
        $students = $class->students()->where('is_active', true)->orderBy('name')->get();
        return view('attendance.manual_create', compact('class', 'students'));
    }

    /** Simpan absensi manual langsung tanpa magic link. */
    public function storeManual(Request $request, Classroom $class): RedirectResponse
    {
        $idSah = $class->students()->where('is_active', true)->pluck('id')->all();

        $data = $request->validate([
            'session_date' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:191'],
            // Mapel & materi: penanda pertemuan guru mapel, sekaligus bekal
            // jurnal mengajar. Kosong berarti presensi harian wali kelas.
            'mapel' => ['nullable', 'string', 'max:100'],
            'materi' => ['nullable', 'string', 'max:500'],
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', 'in:' . implode(',', Attendance::STATUSES)],
            'notes' => ['sometimes', 'array'],
            'notes.*' => ['nullable', 'string', 'max:200'],
        ]);

        $sessionDate = Carbon::parse($data['session_date']);
        $kehadiran = array_intersect_key($data['attendance'], array_flip($idSah));
        $catatan = $data['notes'] ?? [];

        DB::transaction(function () use ($class, $sessionDate, $data, $kehadiran, $catatan, $request) {
            $sequence = AttendanceSession::nextSequenceFor($class->id, $sessionDate);

            $session = $class->attendanceSessions()->create([
                'user_id' => $class->user_id,
                'title' => $data['title'] ?? ('Absensi Manual ' . $sessionDate->translatedFormat('d F Y')),
                'mapel' => $data['mapel'] ?? null,
                'materi' => $data['materi'] ?? null,
                'session_date' => $sessionDate->toDateString(),
                'sequence' => $sequence,
                'token' => \Illuminate\Support\Str::random(32),
                'pin_hash' => \Illuminate\Support\Facades\Hash::make('000000'),
                'expires_at' => now(),
                'status' => 'submitted',
                'submitted_at' => now(),
                'submitted_ip' => $request->ip(),
            ]);

            foreach ($kehadiran as $studentId => $status) {
                $session->attendances()->create([
                    'user_id' => $class->user_id,
                    'student_id' => $studentId,
                    'status' => $status,
                    'note' => trim(strip_tags((string) ($catatan[$studentId] ?? ''))) ?: null,
                ]);
            }
        });

        return redirect()->route('classes.attendance.index', $class)
            ->with('success', 'Absensi manual tanggal ' . $sessionDate->translatedFormat('d F Y') . ' berhasil disimpan!');
    }

    /** Buat sesi baru (Magic Link). */
    public function store(Request $request, Classroom $class): RedirectResponse
    {
        $data = $request->validate([
            'session_date' => ['nullable', 'date'],
            'schedule_id' => ['nullable', Rule::exists('schedules', 'id')->where('class_id', $class->id)],
            'title' => ['nullable', 'string', 'max:191'],
            'ttl_minutes' => ['nullable', 'integer', 'between:5,720'],
            'send_wa' => ['sometimes', 'boolean'],
            'target_number' => ['nullable', 'string', 'max:20'],
            'force_new' => ['sometimes', 'boolean'],
        ]);

        $customDate = !empty($data['session_date']) ? Carbon::parse($data['session_date']) : null;
        $targetDate = $customDate ?? today();

        $schedule = isset($data['schedule_id']) ? Schedule::find($data['schedule_id']) : null;

        $max = (int) config('walikelas.max_sessions_per_day', 3);
        $sesiHariIni = $class->attendanceSessions()
            ->whereDate('session_date', $targetDate)
            ->orderByDesc('sequence')->orderByDesc('id')
            ->get();

        /*
         * Sesi yang masih terbuka diarahkan ulang, bukan ditumpuk. Tanpa ini
         * setiap klik "Buat Sesi" melahirkan sesi baru sampai kuota harian
         * habis, dan magic link yang sudah terlanjur dikirim ke petugas jadi
         * menunjuk sesi yang bukan lagi yang terbaru. Sesi tambahan hanya lahir
         * bila wali kelas memintanya lewat centang "buat sesi baru".
         */
        if (!$request->boolean('force_new') && !$customDate) {
            $terbuka = $sesiHariIni->first(fn ($s) => $s->isOpen());

            if ($terbuka) {
                return redirect()->route('classes.attendance.show', [$class, $terbuka])
                    ->with('info', 'Sesi absensi hari ini masih terbuka. Centang "buat sesi baru" bila memang perlu sesi tambahan.');
            }
        }

        if ($sesiHariIni->count() >= $max && !$customDate) {
            $terakhir = $sesiHariIni->first();

            return redirect()->route('classes.attendance.show', [$class, $terakhir])
                ->with('warning', "Batas {$max} sesi absensi per hari untuk kelas ini sudah tercapai.");
        }

        ['session' => $session, 'pin' => $pin] = $this->service->create(
            classroom: $class,
            schedule: $schedule,
            title: $data['title'] ?? null,
            ttlMinutes: $data['ttl_minutes'] ?? null,
            date: $customDate,
        );

        $sender = $class->owner?->whatsapp_number;
        $target = $this->bolehKirimWa($class)
            ? ($data['target_number'] ?? $this->defaultRecipient($class))
            : null;

        $sent = false;
        $sendWa = $request->has('send_wa') ? $request->boolean('send_wa') : true;
        if ($sendWa && $target) {
            $sent = $this->service->dispatchMagicLink($session, $target, $pin, $sender);
        }

        return redirect()->route('classes.attendance.show', [$class, $session])
            ->with('success', 'Sesi absensi dibuat.' . ($sent ? ' Magic link sedang dikirim via WhatsApp.' : ''))
            ->with('daily_pin', $pin)
            ->with('wa_target', $target);
    }

    /** Detail sesi: magic link, PIN, QR, dan rekap. */
    public function show(Classroom $class, AttendanceSession $attendanceSession): View
    {
        abort_if($attendanceSession->class_id !== $class->id, 404);

        $attendanceSession->load([
            'attendances.student',
            'attendances' => fn ($q) => $q->withCount('revisions'),
            'schedule',
        ]);

        $attendances = $attendanceSession->attendances
            ->sortBy(fn ($a) => $a->student->name ?? '');

        return view('attendance.show', [
            'classroom' => $class,
            'session' => $attendanceSession,
            'attendances' => $attendances,
            'summary' => $this->service->summary($attendanceSession),
            // PIN plaintext hanya ada di flash session sesaat setelah sesi
            // dibuat / dikirim ulang — sengaja tidak bisa diambil dari database
            // karena yang tersimpan hanya hash-nya.
            'pin' => session('daily_pin'),
            'waTarget' => session('wa_target'),
        ]);
    }

    public function edit(Classroom $class, AttendanceSession $attendanceSession): View
    {
        abort_if($attendanceSession->class_id !== $class->id, 404);

        // revisions + revisions_count ikut dimuat: blade menampilkan badge
        // "n× dikoreksi" dan riwayatnya di tempat, jadi memuatnya belakangan
        // berarti satu query per siswa.
        $attendanceSession->load([
            'attendances.student',
            'attendances.revisions.user',
            'attendances' => fn ($q) => $q->withCount('revisions'),
        ]);
        $students = $class->students()->where('is_active', true)->orderBy('name')->get();

        return view('attendance.edit', [
            'classroom' => $class,
            'session' => $attendanceSession,
            'students' => $students,
            'absensi' => $attendanceSession->attendances->keyBy('student_id'),
        ]);
    }

    public function update(Request $request, Classroom $class, AttendanceSession $attendanceSession): RedirectResponse
    {
        abort_if($attendanceSession->class_id !== $class->id, 404);

        $idSah = $class->students()->where('is_active', true)->pluck('id')->all();

        $data = $request->validate([
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', 'in:' . implode(',', Attendance::STATUSES)],
            'notes' => ['sometimes', 'array'],
            'notes.*' => ['nullable', 'string', 'max:200'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $kehadiran = array_intersect_key($data['attendance'], array_flip($idSah));
        $catatan = $data['notes'] ?? [];

        $jumlahKoreksi = DB::transaction(function () use ($attendanceSession, $kehadiran, $catatan, $data) {
            $lama = $attendanceSession->attendances()->get()->keyBy('student_id');
            $koreksi = 0;

            foreach ($kehadiran as $studentId => $status) {
                $baris = $lama->get($studentId);
                $catatanBaru = trim(strip_tags((string) ($catatan[$studentId] ?? ''))) ?: null;

                if ($baris === null) {
                    $baru = $attendanceSession->attendances()->create([
                        'user_id' => $attendanceSession->user_id,
                        'student_id' => $studentId,
                        'status' => $status,
                        'note' => $catatanBaru,
                    ]);
                    $this->catatRevisi($baru, self::TANPA_STATUS_AWAL, $status, $data['reason']);
                    $koreksi++;
                } else {
                    $statusBerubah = $baris->status !== $status;
                    $catatanBerubah = $baris->note !== $catatanBaru;

                    if ($statusBerubah || $catatanBerubah) {
                        $this->catatRevisi($baris, $baris->status, $status, $data['reason']);
                        $baris->update([
                            'status' => $status,
                            'note' => $catatanBaru,
                        ]);
                        $koreksi++;
                    }
                }
            }

            return $koreksi;
        });

        // Membedakan "tersimpan" dari "tidak ada yang berubah" mencegah wali
        // kelas mengira koreksinya masuk padahal ia tidak mengubah apa pun.
        return redirect()->route('classes.attendance.show', [$class, $attendanceSession])
            ->with(
                $jumlahKoreksi > 0 ? 'success' : 'info',
                $jumlahKoreksi > 0
                    ? 'Koreksi absensi berhasil disimpan.'
                    : 'Tidak ada perubahan yang perlu disimpan.'
            );
    }

    /**
     * Nama kolom mengikuti tabel `attendance_revisions`: user_id / from_status /
     * to_status. Catatan (note) dan IP sengaja tidak disimpan — tabel jejak
     * audit ini hanya merekam perpindahan status beserta alasannya.
     */
    private function catatRevisi(
        Attendance $attendance,
        string $fromStatus,
        string $toStatus,
        string $reason
    ): void {
        AttendanceRevision::create([
            'attendance_id' => $attendance->id,
            'user_id' => auth()->id(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
        ]);
    }

    private function defaultRecipient(Classroom $class): ?string
    {
        // 1. Prioritas Utama: Khusus 'seksi_absensi'
        $seksiIds = DB::table('organization_structures')
            ->where('class_id', $class->id)
            ->where('role', 'seksi_absensi')
            ->pluck('student_id');

        if ($seksiIds->isNotEmpty()) {
            $phone = $class->students()
                ->whereIn('id', $seksiIds)
                ->where('is_active', true)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->where('phone', 'not like', '%123456%')
                ->value('phone');
            if ($phone) return $phone;
        }

        // 2. Prioritas Kedua: 'ketua' atau 'sekretaris'
        $pengurusIds = DB::table('organization_structures')
            ->where('class_id', $class->id)
            ->whereIn('role', ['ketua', 'sekretaris'])
            ->pluck('student_id');

        if ($pengurusIds->isNotEmpty()) {
            $phone = $class->students()
                ->whereIn('id', $pengurusIds)
                ->where('is_active', true)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->where('phone', 'not like', '%123456%')
                ->value('phone');
            if ($phone) return $phone;
        }

        // 3. Fallback: Nomor WhatsApp Wali Kelas
        return $class->owner?->whatsapp_number;
    }
}
