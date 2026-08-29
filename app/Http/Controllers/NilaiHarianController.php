<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Classroom;
use App\Support\PeriodeLaporan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Nilai harian guru mapel, ditata per Capaian Pembelajaran.
 *
 * Alurnya sengaja sama persis dengan mengisi presensi: buka satu kali, isi
 * sekelas, simpan. Guru yang sudah terbiasa mengisi absensi tidak perlu
 * mempelajari kebiasaan baru.
 *
 * Ini lapisan SEBELUM e-Rapor, bukan penggantinya — tempat mencatat nilai
 * sehari-hari yang nantinya direkap, bukan buku nilai rapor lengkap dengan
 * bobot dan aturan agregasi yang berbeda tiap sekolah.
 */
class NilaiHarianController extends Controller
{
    public function index(Request $request, Classroom $class): View
    {
        $periode = PeriodeLaporan::resolve($request);
        $mapelDiampu = $class->mapelDiampu();
        $mapelDipilih = $this->mapelDipilih($request, $mapelDiampu);

        $jenisDipilih = in_array($request->query('jenis'), array_keys(Assessment::jenisTersedia()), true)
            ? $request->query('jenis')
            : null;

        $penilaian = $class->assessments()
            ->whereBetween('assessment_date', [
                $periode['awal']->copy()->startOfDay(),
                $periode['akhir']->copy()->endOfDay(),
            ])
            ->when(filled($mapelDipilih), fn ($q) => $q->where('mapel', $mapelDipilih))
            ->when(filled($jenisDipilih), fn ($q) => $q->where('jenis', $jenisDipilih))
            ->with('scores')
            ->orderByDesc('assessment_date')
            ->orderByDesc('id')
            ->get();

        return view('nilai.index', [
            'classroom' => $class,
            'periode' => $periode,
            'penilaian' => $penilaian,
            'mapelDiampu' => $mapelDiampu,
            'mapelDipilih' => $mapelDipilih,
            'jenisDipilih' => $jenisDipilih,
        ]);
    }

    /**
     * Leger nilai satu semester: baris siswa, kolom mapel.
     *
     * Inilah bentuk yang dipakai wali kelas saat menyusun rapor — bukan daftar
     * penilaian satu per satu, melainkan satu tabel yang bisa dibaca sekali
     * lihat untuk melihat siapa yang belum punya nilai di mapel mana.
     */
    public function rekap(Request $request, Classroom $class): View
    {
        $data = $request->validate([
            'semester' => ['nullable', 'integer', 'in:1,2'],
            'jenis' => ['nullable', Rule::in([Assessment::JENIS_PTS, Assessment::JENIS_PAS])],
        ]);

        $semester = (int) ($data['semester'] ?? $this->semesterBerjalan());
        $jenis = $data['jenis'] ?? Assessment::JENIS_PTS;

        $penilaian = $class->assessments()
            ->where('jenis', $jenis)
            ->where('semester', $semester)
            ->with('scores')
            ->orderBy('mapel')
            ->get();

        $students = $class->students()->where('is_active', true)->orderBy('name')->get();

        /*
         * Mapel diambil dari penilaian yang benar-benar ada, bukan dari
         * mapelDiampu() kelas: pada kelas perwalian daftar itu kosong, dan
         * wali kelas justru mencatat nilai dari banyak mapel yang diajar
         * orang lain. Null menjadi '-' supaya penilaian tanpa mapel tetap
         * punya kolom, bukan menghilang tanpa jejak.
         */
        $mapel = $penilaian->map(fn ($p) => $p->mapel ?: '-')->unique()->sort()->values();

        /*
         * [student_id][mapel] => ['nilai' => rata-rata, 'jumlah' => berapa
         * penilaian menyumbang]. "jumlah" ikut dibawa karena rata-rata diam
         * dari dua penilaian menyembunyikan kekeliruan yang lazim: satu mapel
         * tidak sengaja dinilai dua kali untuk semester dan jenis yang sama.
         */
        $matriks = [];
        foreach ($penilaian as $p) {
            $kolom = $p->mapel ?: '-';

            foreach ($p->scores as $skor) {
                if ($skor->nilai === null) {
                    continue;
                }

                $matriks[$skor->student_id][$kolom][] = $skor->nilai;
            }
        }

        $rekap = [];
        foreach ($students as $s) {
            foreach ($mapel as $m) {
                $angka = $matriks[$s->id][$m] ?? [];

                $rekap[$s->id][$m] = $angka === [] ? null : [
                    'nilai' => round(array_sum($angka) / count($angka), 1),
                    'jumlah' => count($angka),
                ];
            }
        }

        return view('nilai.rekap', [
            'classroom' => $class,
            'students' => $students,
            'mapel' => $mapel,
            'rekap' => $rekap,
            'semester' => $semester,
            'jenis' => $jenis,
            'adaPenilaian' => $penilaian->isNotEmpty(),
        ]);
    }

    public function create(Request $request, Classroom $class): View
    {
        return view('nilai.form', [
            'classroom' => $class,
            'assessment' => null,
            'students' => $class->students()->where('is_active', true)->orderBy('name')->get(),
            'nilaiTersimpan' => [],
            'mapelDiampu' => $class->mapelDiampu(),
            'mapelPernahDipakai' => $this->mapelPernahDipakai($class),
            // Ditawarkan dari tautan "isi nilai" pada halaman rekap, supaya
            // guru tidak perlu memilih ulang jenis dan semester yang sama.
            'jenisAwal' => in_array($request->query('jenis'), array_keys(Assessment::jenisTersedia()), true)
                ? $request->query('jenis')
                : Assessment::JENIS_HARIAN,
            'semesterAwal' => in_array((int) $request->query('semester'), [1, 2], true)
                ? (int) $request->query('semester')
                : $this->semesterBerjalan(),
        ]);
    }

    /**
     * Mapel yang pernah dipakai di kelas ini, untuk saran isian.
     *
     * Wali kelas mengetik nama mapel sendiri, dan "Matematika" pada satu
     * penilaian tetapi "MTK" pada penilaian berikutnya akan terbaca sebagai
     * dua mapel berbeda di leger nilai. Daftar ini membuat ejaan yang sudah
     * dipakai gampang dipilih ulang.
     *
     * @return array<int, string>
     */
    private function mapelPernahDipakai(Classroom $class): array
    {
        return $class->assessments()
            ->whereNotNull('mapel')
            ->distinct()
            ->orderBy('mapel')
            ->pluck('mapel')
            ->all();
    }

    /**
     * Semester yang sedang berjalan menurut kalender.
     *
     * Ambangnya sama dengan PeriodeLaporan (semester 1 Juli–Desember) supaya
     * rekap nilai dan rekap kehadiran tidak pernah menunjuk rentang yang
     * berbeda untuk sebutan semester yang sama.
     */
    private function semesterBerjalan(): int
    {
        return now()->month >= 7 ? 1 : 2;
    }

    public function store(Request $request, Classroom $class): RedirectResponse
    {
        $data = $this->validasi($request, $class);

        $assessment = DB::transaction(function () use ($class, $data) {
            $assessment = $class->assessments()->create([
                'user_id' => $class->user_id,
                'jenis' => $data['jenis'],
                'semester' => (int) $data['semester'],
                'mapel' => $data['mapel'] ?? null,
                'capaian_pembelajaran' => $data['capaian_pembelajaran'] ?? null,
                'assessment_date' => $data['assessment_date'],
            ]);

            $this->simpanNilai($assessment, $class, $data['nilai'] ?? [], $data['catatan'] ?? []);

            return $assessment;
        });

        return redirect()
            ->route('classes.nilai.edit', [$class, $assessment])
            ->with('success', 'Penilaian tersimpan.');
    }

    public function edit(Classroom $class, Assessment $assessment): View
    {
        abort_unless($assessment->class_id === $class->id, 404);

        return view('nilai.form', [
            'classroom' => $class,
            'assessment' => $assessment,
            'students' => $class->students()->where('is_active', true)->orderBy('name')->get(),
            'nilaiTersimpan' => $assessment->scores->keyBy('student_id'),
            'mapelDiampu' => $class->mapelDiampu(),
            'mapelPernahDipakai' => $this->mapelPernahDipakai($class),
            'jenisAwal' => $assessment->jenis,
            'semesterAwal' => $assessment->semester ?? $this->semesterBerjalan(),
        ]);
    }

    public function update(Request $request, Classroom $class, Assessment $assessment): RedirectResponse
    {
        abort_unless($assessment->class_id === $class->id, 404);

        $data = $this->validasi($request, $class);

        DB::transaction(function () use ($assessment, $class, $data) {
            $assessment->update([
                'jenis' => $data['jenis'],
                'semester' => (int) $data['semester'],
                'mapel' => $data['mapel'] ?? null,
                'capaian_pembelajaran' => $data['capaian_pembelajaran'] ?? null,
                'assessment_date' => $data['assessment_date'],
            ]);

            $this->simpanNilai($assessment, $class, $data['nilai'] ?? [], $data['catatan'] ?? []);
        });

        return back()->with('success', 'Perubahan nilai tersimpan.');
    }

    public function destroy(Classroom $class, Assessment $assessment): RedirectResponse
    {
        abort_unless($assessment->class_id === $class->id, 404);

        $assessment->delete();

        return redirect()
            ->route('classes.nilai.index', $class)
            ->with('success', 'Penilaian dihapus.');
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, Classroom $class): array
    {
        return $request->validate([
            'jenis' => ['required', Rule::in(array_keys(Assessment::jenisTersedia()))],
            'semester' => ['required', 'integer', 'in:1,2'],
            /*
             * Capaian Pembelajaran hanya bermakna pada nilai harian. PTS dan
             * PAS menilai satu semester penuh sekaligus, bukan satu capaian —
             * mewajibkannya di sana memaksa wali kelas mengarang isian yang
             * tidak berarti apa-apa hanya supaya formulirnya mau tersimpan.
             */
            'capaian_pembelajaran' => [
                Rule::requiredIf(fn () => $request->input('jenis') === Assessment::JENIS_HARIAN),
                'nullable', 'string', 'max:255',
            ],
            'assessment_date' => ['required', 'date'],
            /*
             * Wajib pada PTS/PAS. Leger nilai disusun per mapel; penilaian
             * rapor tanpa nama mapel menumpuk pada satu kolom "-" dan rekapnya
             * kehilangan seluruh gunanya. Pada nilai harian tetap boleh kosong,
             * karena kelas perwalian memang tidak selalu punya mapel.
             */
            'mapel' => [
                Rule::requiredIf(fn () => in_array(
                    $request->input('jenis'),
                    [Assessment::JENIS_PTS, Assessment::JENIS_PAS],
                    true,
                )),
                'nullable', 'string', 'max:100',
            ],
            'nilai' => ['sometimes', 'array'],
            /*
             * `nullable` DIPERTAHANKAN dengan sengaja: kolom yang dibiarkan
             * kosong berarti "belum dinilai", bukan nol. Memaksanya required
             * akan menghalangi guru menyimpan penilaian yang baru terisi
             * separuh — keadaan yang lumrah saat ada siswa sakit ketika
             * ulangan berlangsung.
             */
            'nilai.*' => ['nullable', 'integer', 'between:0,100'],
            'catatan' => ['sometimes', 'array'],
            'catatan.*' => ['nullable', 'string', 'max:200'],
        ], [
            'nilai.*.between' => 'Nilai harus antara 0 sampai 100.',
            'nilai.*.integer' => 'Nilai harus berupa angka bulat.',
        ]);
    }

    /**
     * Simpan nilai seluruh siswa.
     *
     * Hanya siswa kelas ini yang diterima: daftar id datang dari formulir dan
     * tidak boleh dipercaya begitu saja, kalau tidak nilai bisa disisipkan ke
     * siswa kelas lain lewat request yang disusun sendiri.
     *
     * @param  array<int, mixed>  $nilai
     * @param  array<int, mixed>  $catatan
     */
    private function simpanNilai(Assessment $assessment, Classroom $class, array $nilai, array $catatan): void
    {
        $idSah = $class->students()->where('is_active', true)->pluck('id')->all();

        foreach ($idSah as $studentId) {
            $angka = $nilai[$studentId] ?? null;

            $assessment->scores()->updateOrCreate(
                ['student_id' => $studentId],
                [
                    'user_id' => $class->user_id,
                    // '' dari input kosong bukan nol — dijadikan null supaya
                    // "belum dinilai" tetap terbedakan dari "dapat nol".
                    'nilai' => ($angka === null || $angka === '') ? null : (int) $angka,
                    'catatan' => trim((string) ($catatan[$studentId] ?? '')) ?: null,
                ]
            );
        }
    }

    /**
     * Mapel yang sedang ditampilkan, atau null untuk semua.
     *
     * @param  array<int, string>  $mapelDiampu
     */
    private function mapelDipilih(Request $request, array $mapelDiampu): ?string
    {
        $diminta = trim((string) $request->query('mapel', ''));

        if ($diminta === '' || ! in_array($diminta, $mapelDiampu, true)) {
            return count($mapelDiampu) === 1 ? $mapelDiampu[0] : null;
        }

        return $diminta;
    }

    public function exportTemplate(Classroom $class, Assessment $assessment): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless($assessment->class_id === $class->id, 404);
        $filename = 'Template-Nilai-' . \Illuminate\Support\Str::slug($class->name . '-' . $assessment->title) . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\NilaiTemplateExport($class, $assessment), $filename);
    }

    public function importExcel(\Illuminate\Http\Request $request, Classroom $class, Assessment $assessment): \Illuminate\Http\RedirectResponse
    {
        abort_unless($assessment->class_id === $class->id, 404);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $import = new \App\Imports\NilaiImport($class, $assessment);
        \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

        if (!empty($import->errors)) {
            return back()->with('warning', sprintf(
                'Berhasil sinkronisasi %d nilai siswa. Catatan: %s',
                $import->updatedCount,
                implode(' ', array_slice($import->errors, 0, 3))
            ));
        }

        return back()->with('success', sprintf('Berhasil sinkronisasi %d nilai siswa dari Excel.', $import->updatedCount));
    }

}
