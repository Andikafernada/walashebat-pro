<?php

namespace App\Services;

use App\Models\Classroom;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Penyusun data Buku Administrasi Wali Kelas.
 *
 * Ada di kelas tersendiri, bukan di dalam controller, karena data yang sama
 * dipakai dua kali: sekali untuk tampilan HTML di layar, sekali untuk berkas
 * PDF. Keduanya WAJIB memakai perhitungan yang identik — laporan di layar dan
 * laporan yang ditandatangani tidak boleh menghasilkan angka berbeda. Menyalin
 * logikanya ke dua tempat adalah cara tercepat hal itu terjadi.
 *
 * Susunan HTML-nya sengaja TIDAK dibagi, hanya datanya. dompdf hanya memahami
 * CSS 2.1, jadi kelas Tailwind pada tampilan layar mustahil dipakai ulang di
 * PDF; yang bisa disatukan cuma angkanya.
 */
class ClassReportBuilder
{
    /** Bagian yang tersedia, berikut labelnya untuk pemilih di layar. */
    public const SECTIONS = [
        'biodata' => 'Buku Induk (biodata)',
        'siswa' => 'Data Siswa',
        'struktur' => 'Struktur Organisasi',
        'denah' => 'Denah Tempat Duduk',
        'jadwal' => 'Jadwal Pelajaran',
        'kehadiran' => 'Rekap Kehadiran',
        'pelanggaran' => 'Catatan Pelanggaran',
        'poin' => 'Rekap Poin per Siswa',
        'perhatian' => 'Siswa Perlu Perhatian',
        'kas' => 'Buku Kas',
    ];

    /** Ambang batas yang menentukan seorang siswa masuk daftar perhatian. */
    private const BATAS_ALFA = 3;

    /** Keterlambatan berulang adalah pola, bukan kejadian sekali dua kali. */
    private const BATAS_TERLAMBAT = 5;

    private const BATAS_PERSEN_HADIR = 75;

    private const BATAS_POIN = 75;

    /**
     * @param  array<int, string>  $sections
     * @param  string|null  $mapel  Batasi pada satu mata pelajaran. Dipakai guru
     *                              mapel yang mengampu lebih dari satu mapel di
     *                              kelas yang sama — tanpa ini, kehadiran kedua
     *                              mapelnya tercampur menjadi satu persentase
     *                              yang tidak berarti bagi keduanya.
     * @return array<string, mixed>
     */
    public function build(Classroom $class, Carbon $awal, Carbon $akhir, array $sections, ?string $mapel = null): array
    {
        $siswa = $class->students()->orderBy('name')->get();

        /*
         * Batas atas WAJIB memakai akhir hari, bukan tanggalnya saja.
         *
         * Kolom tanggal yang di-cast 'date' bisa tersimpan sebagai
         * "2026-07-25 00:00:00". Membandingkannya dengan string "2026-07-25"
         * membuat baris pada tanggal batas atas dianggap LEBIH BESAR dan
         * tersaring keluar — hari terakhir setiap periode hilang dari laporan
         * tanpa jejak apa pun. Objek Carbon dipakai langsung (bukan
         * toDateString) supaya ikatannya menyertakan jam, dan indeks pada
         * kolom tanggal tetap terpakai — beda dengan whereDate() yang
         * membungkus kolom dalam fungsi.
         */
        $dari = $awal->copy()->startOfDay();
        $hingga = $akhir->copy()->endOfDay();

        $sesi = $class->attendanceSessions()
            ->whereBetween('session_date', [$dari, $hingga])
            ->where('status', '!=', 'cancelled')
            ->when(filled($mapel), fn ($q) => $q->where('mapel', $mapel))
            ->orderBy('session_date')
            ->with('attendances:id,attendance_session_id,student_id,status')
            ->get();

        $pelanggaran = $class->violations()
            ->whereBetween('occurred_on', [$dari, $hingga])
            ->with(['student:id,name', 'type:id,name'])
            ->orderBy('occurred_on')->orderBy('id')
            ->get();

        $kehadiran = $this->rekapKehadiran($siswa, $sesi);
        $poin = $this->rekapPoin($siswa, $pelanggaran);

        return [
            'sections' => $sections,
            'classroom' => $class,
            'awal' => $awal,
            'akhir' => $akhir,
            'siswa' => $siswa,
            'ringkasanSiswa' => [
                'total' => $siswa->count(),
                'lk' => $siswa->where('gender', 'L')->count(),
                'pr' => $siswa->where('gender', 'P')->count(),
            ],
            'struktur' => $class->organizationStructures()
                ->with('student:id,name')->orderBy('sort_order')->get(),
            'grid' => $this->denah($class),
            'jadwal' => $class->schedules()
                ->orderBy('day_of_week')->orderBy('start_time')->get()->groupBy('day_of_week'),
            'kehadiran' => $kehadiran,
            'jumlahPertemuan' => $sesi->count(),
            'persenKelas' => $this->persenKelas($kehadiran),
            'pelanggaran' => $pelanggaran,
            'poin' => $poin,
            'perhatian' => $this->perluPerhatian($kehadiran, $poin),
            'kas' => $this->bukuKas($class, $dari, $hingga),
        ];
    }

    /**
     * Rekap H/S/I/A per siswa.
     *
     * Penyebut persentase adalah jumlah isian yang BENAR-BENAR ada untuk siswa
     * itu, bukan jumlah pertemuan kelas. Siswa yang baru pindah masuk di tengah
     * periode tidak boleh terlihat seperti sering bolos hanya karena pertemuan
     * sebelum dia bergabung ikut dihitung.
     */
    private function rekapKehadiran(Collection $siswa, Collection $sesi): Collection
    {
        $hitung = [];
        $perTanggal = [];

        foreach ($sesi as $s) {
            $tanggal = $s->session_date->toDateString();
            foreach ($s->attendances as $a) {
                $hitung[$a->student_id][$a->status] = ($hitung[$a->student_id][$a->status] ?? 0) + 1;
                $perTanggal[$a->student_id][$tanggal] = $a->status;
            }
        }

        return $siswa->map(function ($s) use ($hitung, $perTanggal) {
            $h = $hitung[$s->id] ?? [];
            $jumlah = [];
            foreach (\App\Models\Attendance::STATUSES as $st) {
                $jumlah[$st] = $h[$st] ?? 0;
            }
            $total = array_sum($jumlah);

            // Terlambat ikut dihitung masuk: anaknya hadir, hanya tidak tepat
            // waktu. Keterlambatannya ditampilkan sebagai kolom tersendiri.
            $masuk = $jumlah['hadir'] + $jumlah['terlambat'];

            return [
                'siswa' => $s,
                'jumlah' => $jumlah,
                'total' => $total,
                'per_tanggal' => $perTanggal[$s->id] ?? [],
                'persen' => $total > 0 ? (int) round($masuk / $total * 100) : 0,
                'perhatian' => $jumlah['alfa'] >= self::BATAS_ALFA
                    || $jumlah['terlambat'] >= self::BATAS_TERLAMBAT,
            ];
        });
    }

    /** Rekap pelanggaran per siswa: jumlah kejadian, poin periode, poin berjalan. */
    private function rekapPoin(Collection $siswa, Collection $pelanggaran): Collection
    {
        $perSiswa = $pelanggaran->groupBy('student_id');

        return $siswa->map(function ($s) use ($perSiswa) {
            $milik = $perSiswa->get($s->id, collect());

            return [
                'siswa' => $s,
                'kejadian' => $milik->count(),
                'poin_periode' => (int) $milik->sum('points'),
                'poin_sekarang' => (int) ($s->discipline_points ?? 0),
                'terakhir' => $milik->last()?->occurred_on,
            ];
        })->filter(fn ($b) => $b['kejadian'] > 0)->values();
    }

    /**
     * Siswa yang perlu ditindaklanjuti, berikut ALASANNYA.
     *
     * Alasannya ikut dibawa, bukan hanya daftar namanya. Laporan yang cuma
     * menyebut "perlu perhatian" memaksa wali kelas membuka menu lain untuk
     * tahu apa masalahnya, dan itu justru pekerjaan yang mau dihemat.
     */
    private function perluPerhatian(Collection $kehadiran, Collection $poin): Collection
    {
        $poinPerSiswa = $poin->keyBy(fn ($b) => $b['siswa']->id);

        return $kehadiran->map(function ($k) use ($poinPerSiswa) {
            $alasan = [];
            $id = $k['siswa']->id;

            if ($k['jumlah']['alfa'] >= self::BATAS_ALFA) {
                $alasan[] = 'Alfa '.$k['jumlah']['alfa'].' kali';
            }

            if ($k['jumlah']['terlambat'] >= self::BATAS_TERLAMBAT) {
                $alasan[] = 'Terlambat '.$k['jumlah']['terlambat'].' kali';
            }

            if ($k['total'] > 0 && $k['persen'] < self::BATAS_PERSEN_HADIR) {
                $alasan[] = 'Kehadiran '.$k['persen'].'%';
            }

            $b = $poinPerSiswa->get($id);

            if ($b && $b['poin_sekarang'] < self::BATAS_POIN) {
                $alasan[] = 'Poin disiplin '.$b['poin_sekarang'];
            }

            if ($b && $b['kejadian'] >= 3) {
                $alasan[] = $b['kejadian'].' pelanggaran';
            }

            return [
                'siswa' => $k['siswa'],
                'alasan' => $alasan,
                'kehadiran' => $k,
                'poin' => $b,
            ];
        })->filter(fn ($b) => $b['alasan'] !== [])->values();
    }

    /** Persentase kehadiran seluruh kelas pada periode ini. */
    private function persenKelas(Collection $kehadiran): int
    {
        $totalIsian = $kehadiran->sum('total');

        if ($totalIsian === 0) {
            return 0;
        }

        $masuk = $kehadiran->sum(fn ($k) => $k['jumlah']['hadir'] + $k['jumlah']['terlambat']);

        return (int) round($masuk / $totalIsian * 100);
    }

    /**
     * Buku kas dengan SALDO BERJALAN.
     *
     * Saldo awal dihitung dari seluruh transaksi sebelum periode ini, sehingga
     * laporan bulan Maret dimulai dari saldo akhir Februari. Tanpa itu, buku
     * kas per periode tidak pernah bisa dicocokkan dengan uang di tangan
     * bendahara.
     *
     * @return array{transaksi: Collection, saldo_awal: int, masuk: int, keluar: int, saldo_akhir: int}
     */
    private function bukuKas(Classroom $class, Carbon $awal, Carbon $akhir): array
    {
        $sebelum = $class->cashBooks()->where('transaction_date', '<', $awal);
        $saldoAwal = (int) (clone $sebelum)->where('type', 'in')->sum('amount')
            - (int) (clone $sebelum)->where('type', 'out')->sum('amount');

        $transaksi = $class->cashBooks()
            ->whereBetween('transaction_date', [$awal, $akhir])
            ->orderBy('transaction_date')->orderBy('id')
            ->get();

        $saldo = $saldoAwal;
        $masuk = 0;
        $keluar = 0;

        foreach ($transaksi as $t) {
            if ($t->type === 'in') {
                $saldo += (int) $t->amount;
                $masuk += (int) $t->amount;
            } else {
                $saldo -= (int) $t->amount;
                $keluar += (int) $t->amount;
            }

            // Disimpan pada model (bukan kolom database) agar tampilan tinggal
            // membacanya tanpa menghitung ulang di dalam loop Blade.
            $t->saldo_berjalan = $saldo;
        }

        return [
            'transaksi' => $transaksi,
            'saldo_awal' => $saldoAwal,
            'masuk' => $masuk,
            'keluar' => $keluar,
            'saldo_akhir' => $saldo,
        ];
    }

    /**
     * Denah tempat duduk sebagai matriks baris × kolom.
     *
     * Setiap baris DISAMAKAN panjangnya dengan baris terlebar. Tanpa itu,
     * baris terakhir yang hanya berisi tiga kursi akan dirender melebar
     * memenuhi halaman, dan denah tercetak tidak lagi menggambarkan posisi
     * duduk yang sebenarnya.
     *
     * @return array<int, array<int, ?string>>
     */
    private function denah(Classroom $class): array
    {
        $grid = [];

        foreach ($class->seats()->with('student:id,name')->get() as $seat) {
            $grid[$seat->row_index][$seat->col_index] = $seat->student?->name;
        }

        if ($grid === []) {
            return [];
        }

        $kolomTerakhir = max(array_map(
            fn ($baris) => empty($baris) ? 0 : max(array_keys($baris)),
            $grid
        ));

        ksort($grid);

        foreach ($grid as $r => $baris) {
            for ($k = 0; $k <= $kolomTerakhir; $k++) {
                $grid[$r][$k] ??= null;
            }
            ksort($grid[$r]);
        }

        return $grid;
    }
}
