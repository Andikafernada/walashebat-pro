<?php

namespace App\Services;

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
    public function build(Student $student, Carbon $awal, Carbon $akhir): array
    {
        $dari = $awal->copy()->startOfDay();
        $hingga = $akhir->copy()->endOfDay();

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
        ];
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
