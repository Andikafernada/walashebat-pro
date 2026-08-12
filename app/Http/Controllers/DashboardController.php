<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Violation;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /*
         * Saringan jenis kelas, pola yang sama dengan halaman Daftar Kelas.
         *
         * Seorang guru bisa memegang satu kelas perwalian sekaligus tujuh kelas
         * yang hanya ia ajar. Sebelumnya dashboard menjumlahkan keduanya menjadi
         * satu angka, padahal keduanya adalah populasi yang berbeda dengan
         * pekerjaan yang berbeda pula.
         */
        $jenis = in_array(request('jenis'), [Classroom::JENIS_PERWALIAN, Classroom::JENIS_AJAR], true)
            ? request('jenis')
            : null;

        $jumlahPerJenis = Classroom::where('is_active', true)
            ->selectRaw('jenis, COUNT(*) as total')
            ->groupBy('jenis')
            ->pluck('total', 'jenis');

        // Semua query otomatis ter-scope ke wali kelas yang login (TenantScope).
        $kelas = Classroom::withCount(['students' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->when($jenis, fn ($q) => $q->where('jenis', $jenis))
            ->orderBy('name')
            ->get();

        $classIds = $kelas->pluck('id');

        /*
         * Biodata, portofolio P5, dan EWS dihitung HANYA dari kelas perwalian,
         * termasuk saat saringannya "Semua".
         *
         * Bukan sekadar soal relevansi, melainkan penyebut yang salah. Template
         * impor kelas ajar sengaja hanya meminta NIS dan nama — biodata orang
         * tua adalah urusan wali kelasnya — sehingga siswa kelas ajar TIDAK
         * MUNGKIN punya nama_ayah. Memasukkan mereka ke penyebut membuat kartu
         * "Biodata Terisi" tidak akan pernah mencapai 100% berapa pun kerja
         * wali kelasnya: pada data yang ada sekarang ia membaca 43% padahal
         * kelas perwalian sudah 87% (33 dari 38). Angka yang mustahil dituntaskan
         * berhenti dibaca sebagai target, dan kartunya jadi sekadar hiasan.
         */
        $idPerwalian = $kelas->where('jenis', Classroom::JENIS_PERWALIAN)->pluck('id');

        /*
         * Tren tujuh hari: SATU query agregat, bukan tujuh.
         *
         * Sebelumnya ada perulangan tujuh kali yang masing-masing menarik
         * SELURUH baris absensi hari itu ke PHP hanya untuk menghitungnya —
         * tujuh perjalanan ke basis data plus tujuh kali menghidupkan ribuan
         * model, setiap kali dashboard dibuka. Menghitung di basis data
         * membuat yang berpindah hanya tujuh baris hasil.
         */
        $awal = today()->subDays(6);
        $masukIn = implode(',', array_fill(0, count(Attendance::STATUS_MASUK), '?'));

        $rekapHarian = Attendance::query()
            ->join('attendance_sessions', 'attendances.attendance_session_id', '=', 'attendance_sessions.id')
            // Ikut saringan: tanpa ini grafik tren tetap menggambar seluruh kelas
            // sementara kartu di atasnya sudah menyempit, dan keduanya bertentangan
            // di layar yang sama.
            ->whereIn('attendance_sessions.class_id', $classIds)
            ->whereBetween('attendance_sessions.session_date', [$awal, today()])
            ->where('attendance_sessions.status', '!=', 'cancelled')
            ->groupBy('tanggal')
            ->selectRaw('DATE(attendance_sessions.session_date) as tanggal')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN attendances.status IN ($masukIn) THEN 1 ELSE 0 END) as masuk", Attendance::STATUS_MASUK)
            ->get()
            ->keyBy('tanggal');

        $chartTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $baris = $rekapHarian->get($date->toDateString());
            $total = (int) ($baris->total ?? 0);
            $present = (int) ($baris->masuk ?? 0);

            $chartTrend[] = [
                'tanggal' => $date->format('d/m'),
                'persen' => $total > 0 ? (int) round($present / $total * 100) : 100,
            ];
        }

        $statusKelas = $this->statusKelasHariIni($kelas);
        $perluPerhatian = $this->perluPerhatian($idPerwalian);
        $gagalKirim = AttendanceSession::with('classroom:id,name')
            ->whereIn('class_id', $classIds)
            ->where('delivery_status', 'failed')
            ->whereDate('session_date', today())
            ->get();
        $waPerluPerhatian = Classroom::whereIn('id', $classIds)->where('auto_attendance', true)->exists()
            && ! auth()->user()->whatsappConnected();

        return view('dashboard', [
            'chartTrend' => $chartTrend,
            'stats' => $this->angkaHariIni($kelas, $idPerwalian),
            'statusKelas' => $statusKelas,
            // Disusun dari koleksi yang SUDAH diambil di atas — papan tugas ini
            // tidak menambah satu query pun.
            'tugasHariIni' => $this->tugasHariIni($statusKelas, $gagalKirim, $waPerluPerhatian, $perluPerhatian),
            // Pelanggaran dan EWS adalah buku pembinaan wali kelas, jadi keduanya
            // dihitung dari kelas perwalian saja dan disembunyikan di mode ajar.
            'perluPerhatian' => $perluPerhatian,
            'pelanggaranTerbaru' => Violation::with(['student:id,name', 'type:id,name', 'classroom:id,name'])
                ->whereIn('class_id', $idPerwalian)
                ->latest('occurred_on')->latest('id')->take(5)->get(),
            'failedDeliveries' => $gagalKirim,
            'waNeedsAttention' => $waPerluPerhatian,
            'jenisDipilih' => $jenis,
            'jumlahPerwalian' => (int) ($jumlahPerJenis[Classroom::JENIS_PERWALIAN] ?? 0),
            'jumlahAjar' => (int) ($jumlahPerJenis[Classroom::JENIS_AJAR] ?? 0),
            // Penentu tunggal apakah kartu khas perwalian dirender sama sekali.
            'adaPerwalian' => $idPerwalian->isNotEmpty(),
        ]);
    }

    /**
     * Papan tugas: apa yang harus dikerjakan pagi ini.
     *
     * Dashboard menjawab "bagaimana keadaannya" lewat kartu angka dan grafik.
     * Pertanyaan yang sebenarnya dibawa wali kelas saat membuka aplikasi jauh
     * lebih sempit: "saya harus apa sekarang?" — dan untuk menjawabnya ia
     * harus membaca enam kartu, satu grafik, lalu menyimpulkan sendiri. Guru
     * yang tidak terbiasa dengan aplikasi berhenti di langkah menyimpulkan.
     *
     * Karena itu setiap butir di sini adalah KALIMAT KERJA dengan angka nyata
     * dan tepat satu tautan tujuan. Yang tidak menuntut tindakan tidak masuk:
     * papan yang memuat kabar baik akan dibaca sebagai hiasan, dan begitu ia
     * jadi hiasan, kabar buruk di dalamnya ikut tidak terbaca.
     *
     * Urutannya menurut akibat bila diabaikan: absensi yang hilang hari ini
     * tidak bisa diulang, sedangkan siswa yang perlu dibina masih bisa ditemui
     * besok.
     *
     * Seluruhnya disusun dari koleksi yang sudah diambil index() — tidak ada
     * satu query pun yang ditambahkan demi papan ini.
     *
     * @param  Collection<int, array<string, mixed>>  $statusKelas
     * @param  Collection<int, AttendanceSession>  $gagalKirim
     * @param  Collection<int, array<string, mixed>>  $perluPerhatian
     * @return list<array<string, mixed>>
     */
    private function tugasHariIni(
        Collection $statusKelas,
        Collection $gagalKirim,
        bool $waPerluPerhatian,
        Collection $perluPerhatian,
    ): array {
        $tugas = [];

        /*
         * WhatsApp putus didahulukan dari apa pun. Selama sesinya mati, tautan
         * presensi tidak akan berangkat ke kelas mana pun — jadi menyuruh guru
         * "kirim tautan" lebih dulu hanya akan berakhir dengan tombol yang
         * ditekan dan tidak terjadi apa-apa.
         */
        if ($waPerluPerhatian) {
            $tugas[] = [
                'nada' => 'bahaya',
                'judul' => 'WhatsApp terputus, absensi otomatis tidak akan terkirim',
                'rinci' => 'Kelas Anda menyalakan absensi otomatis, tetapi sesi WhatsApp sedang tidak tersambung.',
                'aksi' => 'Sambungkan ulang',
                'tautan' => route('whatsapp.index'),
            ];
        }

        if ($gagalKirim->isNotEmpty()) {
            $tugas[] = [
                'nada' => 'bahaya',
                'judul' => $gagalKirim->count().' tautan presensi gagal terkirim hari ini',
                'rinci' => $gagalKirim->map(fn ($s) => $s->classroom?->name)->filter()->join(', '),
                'aksi' => 'Periksa gateway',
                'tautan' => route('whatsapp.index'),
            ];
        }

        // Sesi hangus lebih mendesak daripada yang belum dibuat: tautannya
        // sudah beredar di grup, dan petugas yang mengkliknya menemui halaman
        // mati tanpa tahu harus melapor ke siapa.
        $hangus = $statusKelas->where('keadaan', 'hangus');

        if ($hangus->isNotEmpty()) {
            $tugas[] = [
                'nada' => 'bahaya',
                'judul' => $hangus->count().' sesi absensi hangus sebelum dikirim',
                'rinci' => $hangus->pluck('kelas.name')->join(', ').' — terbitkan tautan baru.',
                'aksi' => 'Terbitkan ulang',
                'tautan' => route('classes.attendance.index', $hangus->first()['kelas']),
            ];
        }

        $belum = $statusKelas->where('keadaan', 'belum');

        if ($belum->isNotEmpty()) {
            $tugas[] = [
                'nada' => 'penting',
                'judul' => $belum->count().' kelas belum diabsen hari ini',
                'rinci' => $belum->pluck('kelas.name')->join(', '),
                'aksi' => 'Mulai absensi',
                'tautan' => route('classes.attendance.index', $belum->first()['kelas']),
            ];
        }

        $menunggu = $statusKelas->where('keadaan', 'menunggu');

        if ($menunggu->isNotEmpty()) {
            $tugas[] = [
                'nada' => 'tenang',
                'judul' => $menunggu->count().' tautan presensi sudah dikirim, menunggu diisi',
                'rinci' => $menunggu->pluck('kelas.name')->join(', ').' — belum perlu tindakan.',
                'aksi' => 'Lihat status',
                'tautan' => route('classes.attendance.index', $menunggu->first()['kelas']),
            ];
        }

        if ($perluPerhatian->isNotEmpty()) {
            $tugas[] = [
                'nada' => 'penting',
                'judul' => $perluPerhatian->count().' siswa perlu dibina',
                'rinci' => $perluPerhatian->take(3)->pluck('siswa.name')->join(', ')
                    .($perluPerhatian->count() > 3 ? ', dan lainnya' : ''),
                'aksi' => 'Lihat siapa',
                'tautan' => '#perlu-perhatian',
            ];
        }

        return $tugas;
    }

    /**
     * Angka utama hari ini.
     *
     * Kehadiran ditampilkan sebagai PERSENTASE dengan penyebut yang jelas,
     * bukan cacah telanjang. Angka "58" tanpa penyebut tidak memberi tahu
     * apa pun — 58 dari 60 dan 58 dari 200 adalah dua keadaan yang sangat
     * berbeda, dan wali kelas tidak bisa membedakannya.
     *
     * @param  Collection<int, Classroom>  $kelas
     * @param  Collection<int, int>  $idPerwalian  Himpunan bagian kelas perwalian.
     * @return array<string, mixed>
     */
    private function angkaHariIni(Collection $kelas, Collection $idPerwalian): array
    {
        $classIds = $kelas->pluck('id');
        $studentQuery = Student::whereIn('class_id', $classIds)->where('is_active', true);
        $totalStudents = (int) $studentQuery->count();

        // Populasi yang biodata & P5-nya memang menjadi tanggung jawab guru ini.
        $siswaPerwalian = Student::whereIn('class_id', $idPerwalian)->where('is_active', true);
        $totalPerwalian = (int) (clone $siswaPerwalian)->count();

        $siswaLaki = (int) (clone $studentQuery)->where('gender', 'L')->count();
        $siswaPerempuan = (int) (clone $studentQuery)->where('gender', 'P')->count();

        $absensiHariIni = Attendance::whereHas(
            'session',
            fn ($q) => $q->whereIn('class_id', $classIds)->whereDate('session_date', today())->where('status', '!=', 'cancelled')
        )->get(['status']);

        $terisi = $absensiHariIni->count();
        $masuk = $absensiHariIni->whereIn('status', Attendance::STATUS_MASUK)->count();
        $sakit = $absensiHariIni->where('status', 'sakit')->count();
        $izin = $absensiHariIni->where('status', 'izin')->count();
        $alfa = $absensiHariIni->where('status', 'alfa')->count();

        /*
         * Biodata dianggap terisi bila nama ayah sudah ada — satu-satunya
         * penanda yang dipakai, dihitung langsung oleh basis data.
         *
         * Sebelumnya di sini ada penebak "data contoh": siswa dibuang dari
         * hitungan bila nama ayahnya mengandung "bambang fauzi" ATAU bila
         * NIS-nya berupa angka 0-37. Aturan kedua itu keliru pada data
         * sungguhan. Banyak sekolah di Indonesia menomori siswanya dengan
         * nomor absen 1-40, dan setiap siswa seperti itu ikut terbuang —
         * sekolah yang biodatanya sudah lengkap tetap membaca "0%" selamanya
         * tanpa cara mengetahui sebabnya. Bahkan pada satu kelas nyata yang
         * ada sekarang, 4 siswa asli sudah salah dibuang karena ini.
         *
         * Penyaringan data contoh memang tidak pantas berada di sini: kode
         * produksi tidak bisa membedakan siswa asli dari siswa buatan, dan
         * menebaknya membuat angka yang dilihat wali kelas menjadi salah.
         */
        $realBiodataCount = (int) (clone $siswaPerwalian)
            ->whereNotNull('nama_ayah')
            ->where('nama_ayah', '<>', '')
            ->count();

        $biodataPercent = $totalPerwalian > 0 ? (int) round(($realBiodataCount / $totalPerwalian) * 100) : 0;

        /*
         * character_reflections, BUKAN character_records.
         *
         * Kartu ini berlabel "Portofolio P5" dan yang ditunggu wali kelas
         * setelah menyebar tautan formulir adalah berapa siswa yang sudah
         * MENGISI — itu tabel refleksi. character_records berisi catatan
         * pengamatan yang ditulis guru sendiri, dan di produksi tabel itu
         * kosong sama sekali (0 baris) sementara refleksi sudah 35: kartunya
         * karena itu selalu menunjukkan 0% bagi setiap wali kelas, berapa pun
         * yang disetor siswanya.
         *
         * Kesalahan yang sama pernah membuat halaman Portofolio Karakter
         * melaporkan 0 — sudah diperbaiki di CharacterPortfolioController,
         * tetapi dashboard tidak ikut.
         */
        $characterStudentsCount = \App\Models\CharacterReflection::whereIn('student_id', (clone $siswaPerwalian)->pluck('id'))
            ->distinct('student_id')
            ->count('student_id');
        $characterPercent = $totalPerwalian > 0 ? (int) round(($characterStudentsCount / $totalPerwalian) * 100) : 0;

        return [
            'classes' => $kelas->count(),
            'students' => $totalStudents,
            'siswa_laki' => $siswaLaki,
            'siswa_perempuan' => $siswaPerempuan,
            'terisi' => $terisi,
            'masuk' => $masuk,
            'sakit' => $sakit,
            'izin' => $izin,
            'alfa' => $alfa,
            /*
             * null, BUKAN 100, saat belum ada satu pun absensi hari ini.
             *
             * Bawaan 100 membuat kartu presensi memamerkan "100%" di pagi hari
             * sebelum absensi apa pun diisi — kabar baik palsu yang persis
             * berlawanan dengan keadaan sebenarnya. Biarkan view yang memilih
             * cara menampilkan ketiadaan data.
             */
            'persen' => $terisi > 0 ? (int) round($masuk / $terisi * 100) : null,
            'biodata_percent' => $biodataPercent,
            'character_percent' => $characterPercent,
            // Penyebut kedua kartu di atas, supaya view bisa menyebutkan
            // cakupannya dan angkanya tidak terbaca sebagai "dari seluruh siswa".
            'siswa_perwalian' => $totalPerwalian,
            'violations_today' => Violation::whereIn('class_id', $idPerwalian)->whereDate('occurred_on', today())->count(),
        ];
    }

    /**
     * Status absensi tiap kelas hari ini.
     *
     * Inilah pertanyaan pertama wali kelas setiap pagi: kelas mana yang sudah
     * absen dan mana yang belum. Sebelumnya jawaban itu hanya bisa diperoleh
     * dengan membuka menu absensi satu per satu.
     *
     * @param  Collection<int, Classroom>  $kelas
     * @return Collection<int, array<string, mixed>>
     */
    private function statusKelasHariIni(Collection $kelas): Collection
    {
        if ($kelas->isEmpty()) {
            return collect();
        }

        // Satu query untuk semua kelas, bukan satu query per kelas.
        $sesi = AttendanceSession::with('classroom:id,name')
            ->whereIn('class_id', $kelas->pluck('id'))
            ->whereDate('session_date', today())
            ->where('status', '!=', 'cancelled')
            ->withCount([
                'attendances as terisi_count',
                'attendances as masuk_count' => fn ($q) => $q->whereIn('status', Attendance::STATUS_MASUK),
                'attendances as alfa_count' => fn ($q) => $q->where('status', 'alfa'),
            ])
            ->orderByDesc('sequence')
            ->get()
            ->groupBy('class_id');

        return $kelas->map(function ($k) use ($sesi) {
            $milik = $sesi->get($k->id);
            $terbaru = $milik?->first();

            $keadaan = match (true) {
                $terbaru === null => 'belum',
                $terbaru->status === 'submitted' => 'selesai',
                $terbaru->isExpired() => 'hangus',
                default => 'menunggu',
            };

            return [
                'kelas' => $k,
                'sesi' => $terbaru,
                'keadaan' => $keadaan,
                'total' => (int) $k->students_count,
                'terisi' => (int) ($terbaru->terisi_count ?? 0),
                'masuk' => (int) ($terbaru->masuk_count ?? 0),
                'alfa' => (int) ($terbaru->alfa_count ?? 0),
                'persen' => ($terbaru?->terisi_count ?? 0) > 0
                    ? (int) round($terbaru->masuk_count / $terbaru->terisi_count * 100)
                    : null,
            ];
        });
    }

    /**
     * Siswa yang perlu ditindaklanjuti, lintas kelas.
     *
     * Ambangnya: alfa 3 kali atau lebih dalam 30 hari terakhir, atau poin
     * disiplin di bawah 75. Alasannya ikut dibawa — daftar nama tanpa sebab
     * memaksa wali kelas membuka menu lain hanya untuk tahu ada apa.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function perluPerhatian(Collection $idPerwalian): Collection
    {
        if ($idPerwalian->isEmpty()) {
            return collect();
        }

        $sejak = today()->subDays(30);

        $alfa = Attendance::query()
            ->where('status', 'alfa')
            ->whereHas('session', fn ($q) => $q->whereIn('class_id', $idPerwalian)
                ->whereDate('session_date', '>=', $sejak))
            ->selectRaw('student_id, COUNT(*) as jumlah')
            ->groupBy('student_id')
            ->having('jumlah', '>=', 3)
            ->pluck('jumlah', 'student_id');

        $poinRendah = Student::whereIn('class_id', $idPerwalian)
            ->where('is_active', true)
            ->where('discipline_points', '<', 75)
            ->pluck('discipline_points', 'id');

        $idSemua = $alfa->keys()->merge($poinRendah->keys())->unique();

        if ($idSemua->isEmpty()) {
            return collect();
        }

        return Student::with('classroom:id,name')
            ->whereIn('id', $idSemua)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($s) use ($alfa, $poinRendah) {
                $alasan = [];

                if ($alfa->has($s->id)) {
                    $alasan[] = 'Alfa '.$alfa[$s->id].'× (30 hari)';
                }

                if ($poinRendah->has($s->id)) {
                    $alasan[] = 'Poin '.$poinRendah[$s->id];
                }

                return ['siswa' => $s, 'alasan' => $alasan];
            })
            ->take(8);
    }
}
