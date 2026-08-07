<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Classroom;
use App\Support\PeriodeLaporan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $penilaian = $class->assessments()
            ->whereBetween('assessment_date', [
                $periode['awal']->copy()->startOfDay(),
                $periode['akhir']->copy()->endOfDay(),
            ])
            ->when(filled($mapelDipilih), fn ($q) => $q->where('mapel', $mapelDipilih))
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
        ]);
    }

    public function store(Request $request, Classroom $class): RedirectResponse
    {
        $data = $this->validasi($request, $class);

        $assessment = DB::transaction(function () use ($class, $data) {
            $assessment = $class->assessments()->create([
                'user_id' => $class->user_id,
                'mapel' => $data['mapel'] ?? null,
                'capaian_pembelajaran' => $data['capaian_pembelajaran'],
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
        ]);
    }

    public function update(Request $request, Classroom $class, Assessment $assessment): RedirectResponse
    {
        abort_unless($assessment->class_id === $class->id, 404);

        $data = $this->validasi($request, $class);

        DB::transaction(function () use ($assessment, $class, $data) {
            $assessment->update([
                'mapel' => $data['mapel'] ?? null,
                'capaian_pembelajaran' => $data['capaian_pembelajaran'],
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
            'capaian_pembelajaran' => ['required', 'string', 'max:255'],
            'assessment_date' => ['required', 'date'],
            'mapel' => ['nullable', 'string', 'max:100'],
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
}
