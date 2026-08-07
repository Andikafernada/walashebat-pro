<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Services\ClassReportBuilder;
use App\Support\PeriodeLaporan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ClassReportBuilder $builder) {}

    /** Buku administrasi wali kelas, tampilan layar. */
    public function full(Request $request, Classroom $class): View
    {
        $periode = PeriodeLaporan::resolve($request);
        $bagian = $this->resolveBagian($request);

        $data = $this->builder->build($class, $periode['awal'], $periode['akhir'], $bagian);

        return view('reports.full', $data + [
            'periode' => $periode,
            'semuaBagian' => ClassReportBuilder::SECTIONS,
        ]);
    }

    /**
     * Berkas PDF resmi, siap dijilid dan ditandatangani.
     *
     * Tampilan layar TIDAK dipakai ulang di sini. dompdf hanya memahami CSS 2.1
     * sedangkan tampilan layar dibangun dengan Tailwind, jadi merendernya lewat
     * dompdf hanya menghasilkan halaman tanpa gaya sama sekali. Tata letak PDF
     * punya berkas sendiri dengan CSS sederhana; yang dibagi hanyalah datanya.
     */
    public function fullPdf(Request $request, Classroom $class): Response
    {
        $periode = PeriodeLaporan::resolve($request);
        $bagian = $this->resolveBagian($request);

        $data = $this->builder->build($class, $periode['awal'], $periode['akhir'], $bagian);

        $pdf = Pdf::loadView('reports.pdf.administrasi', $data + [
            'periode' => $periode,
            'guru' => $request->user() ?? $class->user,
        ])->setPaper('a4', 'portrait');

        $namaBerkas = sprintf(
            'Administrasi-%s-%s.pdf',
            str($class->name)->slug(),
            $periode['slug']
        );

        return $pdf->download($namaBerkas);
    }

    /** Rekap kehadiran harian (matriks tanggal × siswa). */
    public function attendance(Request $request, Classroom $class): View
    {
        $periode = PeriodeLaporan::resolve($request);
        $mapelDipilih = $this->mapelDipilih($request, $class);

        $data = $this->builder->build($class, $periode['awal'], $periode['akhir'], ['kehadiran'], $mapelDipilih);

        $tanggalKolom = $class->attendanceSessions()
            ->whereBetween('session_date', [
                $periode['awal']->copy()->startOfDay(),
                $periode['akhir']->copy()->endOfDay(),
            ])
            ->where('status', '!=', 'cancelled')
            ->when(filled($mapelDipilih), fn ($q) => $q->where('mapel', $mapelDipilih))
            ->orderBy('session_date')
            ->pluck('session_date')
            ->map(fn ($d) => $d->toDateString())
            ->unique()->values();

        return view('reports.attendance', [
            'classroom' => $class,
            'periode' => $periode,
            // Dipertahankan demi tampilan lama yang masih menyebut $bulan.
            'bulan' => $periode['awal'],
            'tanggalKolom' => $tanggalKolom,
            'rekap' => $data['kehadiran'],
            'jumlahPertemuan' => $data['jumlahPertemuan'],
            'persenKelas' => $data['persenKelas'],
            'perluPerhatian' => $data['kehadiran']->where('perhatian', true)->count(),
            'mapelDiampu' => $class->mapelDiampu(),
            'mapelDipilih' => $mapelDipilih,
        ]);
    }

    /**
     * Analisis kehadiran: siapa yang jarang hadir.
     *
     * Rekap menjawab "siapa hadir kapan"; analisis menjawab pertanyaan yang
     * berbeda dan lebih mendesak bagi guru mapel — siswa mana yang kehadirannya
     * bermasalah, diurutkan dari yang paling rendah, supaya yang perlu
     * ditindaklanjuti berada di baris pertama alih-alih harus dicari sendiri di
     * dalam tabel berisi puluhan nama.
     *
     * Tidak menyimpan apa pun: seluruhnya hitungan dari data presensi yang
     * sudah ada.
     */
    public function analisisKehadiran(Request $request, Classroom $class): View
    {
        $periode = PeriodeLaporan::resolve($request);
        $mapelDipilih = $this->mapelDipilih($request, $class);

        $data = $this->builder->build($class, $periode['awal'], $periode['akhir'], ['kehadiran'], $mapelDipilih);

        /*
         * Diurutkan dari persentase TERENDAH. Siswa tanpa satu pun isian
         * ditaruh paling belakang: persentasenya 0 bukan karena sering bolos,
         * melainkan karena belum pernah tercatat — menempatkannya di puncak
         * daftar "perlu perhatian" adalah tuduhan yang keliru.
         */
        $urut = $data['kehadiran']
            ->sortBy(fn ($k) => [$k['total'] > 0 ? 0 : 1, $k['persen']])
            ->values();

        return view('reports.analisis_kehadiran', [
            'classroom' => $class,
            'periode' => $periode,
            'rekap' => $urut,
            'jumlahPertemuan' => $data['jumlahPertemuan'],
            'persenKelas' => $data['persenKelas'],
            'ambangPerhatian' => 75,
            'mapelDiampu' => $class->mapelDiampu(),
            'mapelDipilih' => $mapelDipilih,
        ]);
    }

    /**
     * Mapel yang sedang ditampilkan, atau null untuk semua.
     *
     * Hanya menerima mapel yang benar-benar diampu di kelas ini; nilai lain
     * diabaikan. Tanpa penjagaan ini, parameter URL sembarangan menghasilkan
     * laporan kosong yang terlihat seperti belum ada data sama sekali.
     */
    private function mapelDipilih(Request $request, Classroom $class): ?string
    {
        $diampu = $class->mapelDiampu();
        $diminta = trim((string) $request->query('mapel', ''));

        if ($diminta === '' || ! in_array($diminta, $diampu, true)) {
            return count($diampu) === 1 ? $diampu[0] : null;
        }

        return $diminta;
    }

    /**
     * Bagian mana yang dicetak.
     *
     * REGRESI: nilainya pernah di-hardcode menjadi array kosong, sehingga
     * in_array() pada tampilan selalu bernilai false dan SELURUH isi laporan
     * tidak pernah tampil — yang tercetak hanya halaman sampul. Default-nya
     * kini seluruh bagian: laporan administrasi tanpa isi tidak ada gunanya,
     * dan pengguna yang tidak memilih apa pun jelas menginginkan semuanya.
     *
     * @return array<int, string>
     */
    private function resolveBagian(Request $request): array
    {
        $tersedia = array_keys(ClassReportBuilder::SECTIONS);

        $request->validate([
            'bagian' => ['nullable', 'array'],
            'bagian.*' => [Rule::in($tersedia)],
        ]);

        /*
         * Kelompok checkbox yang seluruhnya tidak dicentang TIDAK mengirim
         * parameter apa pun. Tanpa penanda, "kosongkan semua" mustahil
         * dibedakan dari kunjungan pertama, dan pengguna yang mematikan semua
         * bagian akan tetap disuguhi laporan lengkap.
         *
         * Field tersembunyi pada formulir menjadi penandanya: ada berarti
         * pilihan datang dari formulir dan harus dihormati apa adanya.
         */
        if (! $request->boolean('pilih_bagian')) {
            return $tersedia;
        }

        return array_values(array_intersect($tersedia, (array) $request->input('bagian', [])));
    }
}
