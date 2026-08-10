<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\CharacterRecord;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Penyusun profil lengkap satu siswa.
 *
 * Alasan keberadaannya: sebelum ini, untuk mengetahui keadaan satu anak wali
 * kelas harus membuka empat menu terpisah lalu menghitung sendiri. Padahal
 * momen yang paling butuh data itu justru yang paling tidak memberi waktu —
 * orang tua sedang menelepon, atau BK sedang menunggu di depan meja.
 *
 * Perhitungannya dipisah dari controller supaya tampilan layar dan berkas PDF
 * memakai angka yang sama persis.
 */
class StudentProfileBuilder
{
    /** Poin awal setiap siswa, sesuai default kolom discipline_points. */
    private const POIN_AWAL = 100;

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  ?int  $semester  Semester untuk nilai rapor. Nilai PTS/PAS
     *                          disandarkan pada semester yang tersimpan, bukan
     *                          pada rentang tanggal — nilai akhir semester 1
     *                          lazim baru dimasukkan pada Januari, yang menurut
     *                          kalender sudah semester 2. Menyaringnya dengan
     *                          tanggal akan membuat nilai itu hilang dari
     *                          profil tanpa jejak. Null berarti disimpulkan
     *                          dari bulan $akhir.
     * @return array<string, mixed>
     */
    public function build(Student $student, Carbon $awal, Carbon $akhir, ?int $semester = null): array
    {
        $dari = $awal->copy()->startOfDay();
        $hingga = $akhir->copy()->endOfDay();
        $semester ??= $hingga->month >= 7 ? 1 : 2;

        $absensi = $student->attendances()
            ->withCount('revisions')
            ->with(['session:id,session_date,sequence,status'])
            ->whereHas('session', fn ($q) => $q
                ->whereBetween('session_date', [$dari, $hingga])
                ->where('status', '!=', 'cancelled'))
            ->get()
            ->sortByDesc(fn ($a) => $a->session?->session_date)
            ->values();

        $pelanggaran = $student->violations()
            ->with('type:id,name')
            ->whereBetween('occurred_on', [$dari, $hingga])
            ->orderByDesc('occurred_on')->orderByDesc('id')
            ->get();

        return [
            'siswa' => $student,
            'awal' => $dari,
            'akhir' => $hingga,
            'kehadiran' => $this->rekapKehadiran($absensi),
            'absensi' => $absensi,
            'pelanggaran' => $pelanggaran,
            'poin' => $this->rekapPoin($student, $pelanggaran),
            'tren' => $this->trenBulanan($student, $hingga),
            'kelengkapan' => $this->kelengkapanData($student),
            'peran' => $student->classroom?->organizationStructures()
                ->where('student_id', $student->id)->get(),
            'nilai' => $this->rekapNilai($student, $dari, $hingga, $semester),
            'karakter' => $this->rekapKarakter($student, $dari, $hingga),
            'semester' => $semester,
        ];
    }

    /**
     * Nilai: rapor (PTS/PAS) satu semester, dan nilai harian pada periode.
     *
     * Keduanya dipisah karena tidak setara. PTS/PAS adalah penilaian rapor,
     * jumlahnya sedikit dan menjadi bahan keputusan; nilai harian jumlahnya
     * banyak dan bergerak. Meleburnya menjadi satu rata-rata menghasilkan
     * angka yang tidak dimaksudkan siapa pun.
     *
     * @return array<string, mixed>
     */
    private function rekapNilai(Student $student, Carbon $dari, Carbon $hingga, int $semester): array
    {
        /*
         * Rapor disaring per SEMESTER, tanpa menyentuh tanggal sama sekali.
         * Lihat catatan pada build(): tanggal memasukkan nilai tidak selalu
         * jatuh di dalam semesternya.
         */
        $rapor = $student->assessmentScores()
            ->whereNotNull('nilai')
            ->whereHas('assessment', fn ($q) => $q
                ->whereIn('jenis', [Assessment::JENIS_PTS, Assessment::JENIS_PAS])
                ->where('semester', $semester))
            ->with('assessment:id,jenis,mapel,semester')
            ->get();

        $perMapel = [];
        foreach ($rapor as $skor) {
            $mapel = $skor->assessment?->mapel ?: '—';
            $perMapel[$mapel][$skor->assessment->jenis][] = $skor->nilai;
        }

        $daftarRapor = [];
        foreach ($perMapel as $mapel => $jenis) {
            $daftarRapor[] = [
                'mapel' => $mapel,
                'pts' => $this->rata($jenis[Assessment::JENIS_PTS] ?? []),
                'pas' => $this->rata($jenis[Assessment::JENIS_PAS] ?? []),
            ];
        }
        usort($daftarRapor, fn ($a, $b) => strcmp($a['mapel'], $b['mapel']));

        // Nilai harian tetap disaring per tanggal: ia memang peristiwa harian,
        // dan periode yang sedang dilihat wali kelas adalah konteksnya.
        $harian = $student->assessmentScores()
            ->whereNotNull('nilai')
            ->whereHas('assessment', fn ($q) => $q
                ->where('jenis', Assessment::JENIS_HARIAN)
                ->whereBetween('assessment_date', [$dari, $hingga]))
            ->with('assessment:id,mapel,assessment_date')
            ->get();

        $perMapelHarian = [];
        foreach ($harian as $skor) {
            $perMapelHarian[$skor->assessment?->mapel ?: '—'][] = $skor->nilai;
        }

        $daftarHarian = [];
        foreach ($perMapelHarian as $mapel => $angka) {
            $daftarHarian[] = [
                'mapel' => $mapel,
                'rata' => $this->rata($angka),
                'jumlah' => count($angka),
            ];
        }
        usort($daftarHarian, fn ($a, $b) => strcmp($a['mapel'], $b['mapel']));

        $semuaRapor = $rapor->pluck('nilai')->all();

        return [
            'rapor' => $daftarRapor,
            'harian' => $daftarHarian,
            'rata_rapor' => $this->rata($semuaRapor),
            'ada' => $daftarRapor !== [] || $daftarHarian !== [],
        ];
    }

    /**
     * Karakter P5, dikelompokkan per dimensi.
     *
     * Yang dicari wali kelas di sini bukan satu angka melainkan bentuknya:
     * anak dengan sepuluh catatan positif pada dimensi Gotong Royong dan
     * tiga catatan negatif pada Kemandirian menuntut pembinaan yang sama
     * sekali berbeda dari anak yang sebaliknya — padahal jumlah bersihnya
     * bisa sama persis.
     *
     * @return array<string, mixed>
     */
    private function rekapKarakter(Student $student, Carbon $dari, Carbon $hingga): array
    {
        $catatan = $student->characterRecords()
            ->with('dimension:id,name')
            ->whereBetween('record_date', [$dari, $hingga])
            ->orderByDesc('record_date')->orderByDesc('id')
            ->get();

        $perDimensi = [];
        foreach ($catatan as $c) {
            $nama = $c->dimension?->name ?: 'Tanpa dimensi';

            $perDimensi[$nama] ??= ['dimensi' => $nama, 'positif' => 0, 'negatif' => 0, 'lainnya' => 0, 'skor' => 0];
            $perDimensi[$nama]['skor'] += (int) $c->score;

            match ($c->type) {
                CharacterRecord::TYPE_POSITIVE,
                CharacterRecord::TYPE_ACHIEVEMENT => $perDimensi[$nama]['positif']++,
                CharacterRecord::TYPE_NEGATIVE => $perDimensi[$nama]['negatif']++,
                default => $perDimensi[$nama]['lainnya']++,
            };
        }

        $daftar = array_values($perDimensi);
        usort($daftar, fn ($a, $b) => strcmp($a['dimensi'], $b['dimensi']));

        return [
            'dimensi' => $daftar,
            'catatan' => $catatan,
            'total' => [
                'positif' => array_sum(array_column($daftar, 'positif')),
                'negatif' => array_sum(array_column($daftar, 'negatif')),
                'lainnya' => array_sum(array_column($daftar, 'lainnya')),
                'skor' => array_sum(array_column($daftar, 'skor')),
            ],
        ];
    }

    /**
     * Rata-rata satu digit di belakang koma, atau null bila tidak ada isian.
     *
     * Null, BUKAN nol: "belum dinilai" dan "dapat nol" adalah dua keadaan yang
     * berbeda, dan menyamakannya sudah pernah membuat rata-rata kelas anjlok
     * oleh siswa yang sebenarnya belum diuji.
     *
     * @param  array<int, int|float>  $angka
     */
    private function rata(array $angka): ?float
    {
        return $angka === [] ? null : round(array_sum($angka) / count($angka), 1);
    }

    /**
     * Rekap H/S/I/A.
     *
     * Penyebut persentase adalah jumlah isian yang ada untuk siswa ini, bukan
     * jumlah pertemuan kelas — siswa yang baru pindah masuk di tengah periode
     * tidak boleh terlihat sering bolos karena pertemuan sebelum dia bergabung
     * ikut dihitung.
     *
     * @return array<string, mixed>
     */
    private function rekapKehadiran(Collection $absensi): array
    {
        $jumlah = [];
        foreach (\App\Models\Attendance::STATUSES as $st) {
            $jumlah[$st] = $absensi->where('status', $st)->count();
        }

        $total = array_sum($jumlah);
        // Terlambat ikut dihitung masuk; lihat Attendance::STATUS_MASUK.
        $masuk = $jumlah['hadir'] + $jumlah['terlambat'];

        return [
            'jumlah' => $jumlah,
            'total' => $total,
            'persen' => $total > 0 ? (int) round($masuk / $total * 100) : null,
            'dikoreksi' => $absensi->where('revisions_count', '>', 0)->count(),
        ];
    }

    /**
     * Poin disiplin: yang berjalan sekarang dan yang hilang pada periode ini.
     *
     * @return array<string, mixed>
     */
    private function rekapPoin(Student $student, Collection $pelanggaran): array
    {
        $sekarang = (int) ($student->discipline_points ?? self::POIN_AWAL);

        return [
            'sekarang' => $sekarang,
            'awal' => self::POIN_AWAL,
            'periode' => (int) $pelanggaran->sum('points'),
            'kejadian' => $pelanggaran->count(),
            // Dipakai untuk lebar bilah, bukan sekadar angka.
            'persen' => (int) max(0, min(100, round($sekarang / self::POIN_AWAL * 100))),
        ];
    }

    /**
     * Tren kehadiran enam bulan terakhir.
     *
     * Angka satu periode saja tidak memberi tahu arahnya. Anak yang bulan ini
     * 78% bisa sedang membaik dari 60%, atau sedang jatuh dari 95% — dan dua
     * keadaan itu menuntut tindakan yang sama sekali berbeda.
     *
     * @return Collection<int, array{label: string, persen: ?int, alfa: int}>
     */
    private function trenBulanan(Student $student, Carbon $akhir): Collection
    {
        $mulai = $akhir->copy()->startOfMonth()->subMonths(5);

        $baris = $student->attendances()
            ->with('session:id,session_date,status')
            ->whereHas('session', fn ($q) => $q
                ->whereBetween('session_date', [$mulai, $akhir])
                ->where('status', '!=', 'cancelled'))
            ->get()
            ->groupBy(fn ($a) => $a->session?->session_date?->format('Y-m'));

        return collect(range(0, 5))->map(function ($i) use ($mulai, $baris) {
            $bulan = $mulai->copy()->addMonths($i);
            $isi = $baris->get($bulan->format('Y-m'), collect());
            $total = $isi->count();

            return [
                'label' => $bulan->translatedFormat('M'),
                'persen' => $total > 0
                    ? (int) round($isi->whereIn('status', \App\Models\Attendance::STATUS_MASUK)->count() / $total * 100)
                    : null,
                'alfa' => $isi->where('status', 'alfa')->count(),
                'terlambat' => $isi->where('status', 'terlambat')->count(),
            ];
        });
    }

    /**
     * Kelengkapan biodata, berikut daftar kolom yang masih kosong.
     *
     * Daftarnya ikut dikembalikan, bukan cuma persentase: wali kelas yang
     * diminta melengkapi data butuh tahu APA yang kurang, bukan sekadar bahwa
     * masih ada yang kurang.
     *
     * @return array{persen: int, kosong: array<int, string>}
     */
    private function kelengkapanData(Student $student): array
    {
        $wajib = [
            'nis' => 'NIS',
            'nisn' => 'NISN',
            'gender' => 'Jenis kelamin',
            'tempat_lahir' => 'Tempat lahir',
            'tanggal_lahir' => 'Tanggal lahir',
            'agama' => 'Agama',
            'address' => 'Alamat',
            'nama_ayah' => 'Nama ayah',
            'nama_ibu' => 'Nama ibu',
            'parent_phone' => 'HP orang tua',
        ];

        $kosong = [];

        foreach ($wajib as $kolom => $label) {
            if (blank($student->{$kolom})) {
                $kosong[] = $label;
            }
        }

        $terisi = count($wajib) - count($kosong);

        return [
            'persen' => (int) round($terisi / count($wajib) * 100),
            'kosong' => $kosong,
        ];
    }
}
