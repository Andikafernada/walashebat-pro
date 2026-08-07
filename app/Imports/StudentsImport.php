<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import siswa dari Excel/CSV untuk SATU kelas.
 *
 * Perbedaan penting dari StudentsProfileImport yang lama:
 *
 * - Bisa MEMBUAT siswa baru, bukan cuma memperbarui yang sudah ada. Versi lama
 *   melewati setiap NIS yang tidak ditemukan, padahal justru menambah siswa
 *   massal yang jadi alasan fitur ini dibuat.
 * - Terikat ke satu kelas. Versi lama mencari Student::where('nis', ...)
 *   tanpa batas kelas, jadi NIS "001" di dua kelas berbeda saling menimpa.
 * - Tidak memakai WithValidation. Satu baris cacat tidak lagi menggagalkan
 *   seluruh berkas; masalahnya dicatat per baris dan dilaporkan ke guru.
 * - Nomor baris tetap akurat karena baris kosong dilewati manual, bukan lewat
 *   SkipsEmptyRows yang menggeser penomoran.
 */
class StudentsImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    /** Header berkas (sudah di-slug) => kolom database. */
    private const MAP = [
        'nis' => 'nis',
        'nisn' => 'nisn',
        'nama' => 'name',
        'jenis_kelamin' => 'gender',
        'tempat_lahir' => 'tempat_lahir',
        'tanggal_lahir' => 'tanggal_lahir',
        'agama' => 'agama',
        'anak_ke' => 'anak_ke',
        'jumlah_saudara' => 'jumlah_saudara',
        'golongan_darah' => 'golongan_darah',
        'tinggi_badan' => 'tinggi_badan_cm',
        'berat_badan' => 'berat_badan_kg',
        'alamat' => 'address',
        'rt_rw' => 'rt_rw',
        'kelurahan' => 'kelurahan',
        'kecamatan' => 'kecamatan',
        'hp_siswa' => 'phone',
        'hp_ortu' => 'parent_phone',
        'jarak_rumah_km' => 'jarak_rumah_km',
        'moda_transportasi' => 'moda_transportasi',
        'nama_ayah' => 'nama_ayah',
        'pekerjaan_ayah' => 'pekerjaan_ayah',
        'nama_ibu' => 'nama_ibu',
        'pekerjaan_ibu' => 'pekerjaan_ibu',
        'nama_wali' => 'nama_wali',
        'pekerjaan_wali' => 'pekerjaan_wali',
        'alamat_ortu' => 'alamat_ortu',
        'asal_sekolah' => 'asal_sekolah',
        'tahun_masuk' => 'tahun_masuk',
        'kompetensi_keahlian' => 'kompetensi_keahlian',
        'hobi' => 'hobi',
        'cita_cita' => 'cita_cita',
        'penerima_kip' => 'penerima_kip',
        'penerima_pkh' => 'penerima_pkh',

        // Alias supaya berkas lama / variasi penulisan tetap terbaca.
        'nama_lengkap' => 'name',
        'nama_siswa' => 'name',
        'gender' => 'gender',
        'l_p' => 'gender',
        'no_hp_siswa' => 'phone',
        'no_hp_ortu' => 'parent_phone',
        'hp_orang_tua' => 'parent_phone',
        'telepon_ortu' => 'parent_phone',
        'tinggi_badan_cm' => 'tinggi_badan_cm',
        'berat_badan_kg' => 'berat_badan_kg',
    ];

    private const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'];

    private const GOLONGAN_DARAH = ['A', 'B', 'AB', 'O', 'Tidak Tahu'];

    private int $dibuat = 0;

    private int $diperbarui = 0;

    private int $dilewati = 0;

    /** @var array<int, array{baris:int, nama:string, pesan:string}> */
    private array $catatan = [];

    private int $baris = 1; // baris 1 adalah header

    public function __construct(private readonly Classroom $classroom) {}

    public function chunkSize(): int
    {
        return 200;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->baris++;

            $data = $this->petakan($row);

            // Baris benar-benar kosong: lewati tanpa dihitung dan tanpa catatan.
            if ($this->kosongSeluruhnya($data)) {
                continue;
            }

            try {
                $this->prosesBaris($data);
            } catch (\Throwable $e) {
                $this->dilewati++;
                $this->catatan[] = [
                    'baris' => $this->baris,
                    'nama' => (string) ($data['name'] ?? $data['nis'] ?? '—'),
                    'pesan' => 'Gagal disimpan: '.$e->getMessage(),
                ];
            }
        }
    }

    private function prosesBaris(array $data): void
    {
        $nis = trim((string) ($data['nis'] ?? ''));
        $nama = trim((string) ($data['name'] ?? ''));

        $siswa = $this->cariSiswa($nis, $nama);

        if (! $siswa && $nama === '') {
            $this->dilewati++;
            $this->catatan[] = [
                'baris' => $this->baris,
                'nama' => $nis !== '' ? "NIS {$nis}" : '—',
                'pesan' => 'Nama kosong dan siswa belum ada, jadi tidak bisa dibuat.',
            ];

            return;
        }

        $bersih = $this->bersihkan($data);

        if ($siswa) {
            // Sel yang dibiarkan kosong TIDAK menghapus data yang sudah ada.
            // Ini penting untuk alur ekspor-edit-unggah: guru cuma mengisi
            // kolom yang ingin diperbaiki.
            $siswa->fill(array_filter($bersih, fn ($v) => $v !== null && $v !== ''));
            $adaPerubahan = $siswa->isDirty();
            $siswa->save();

            $adaPerubahan ? $this->diperbarui++ : $this->dilewati++;

            if (! $adaPerubahan) {
                $this->catatan[] = [
                    'baris' => $this->baris,
                    'nama' => $siswa->name,
                    'pesan' => 'Tidak ada perubahan, data sudah sama.',
                ];
            }

            return;
        }

        $this->classroom->students()->create($bersih + [
            'user_id' => $this->classroom->user_id,
            'name' => $nama,
            'is_active' => true,
        ]);

        $this->dibuat++;
    }

    /** Cari berdasarkan NIS dulu, lalu nama, selalu dibatasi kelas ini. */
    private function cariSiswa(string $nis, string $nama): ?Student
    {
        if ($nis !== '') {
            $siswa = $this->classroom->students()->where('nis', $nis)->first();

            if ($siswa) {
                return $siswa;
            }
        }

        if ($nama === '') {
            return null;
        }

        /*
         * Pencocokan nama disamakan dengan formulir biodata publik.
         *
         * Perbandingan LOWER(name) yang lama menganggap "SALSA VIRLYANA",
         * "Salsa  Virlyana", dan "Salsa.Virlyana" sebagai tiga orang berbeda,
         * sehingga alur ekspor-sunting-unggah ulang — pemakaian yang justru
         * dianjurkan halaman impor — melahirkan siswa kembar setiap kali ejaan
         * di berkasnya sedikit bergeser.
         */
        $cocok = $this->classroom->students()
            ->get()
            ->filter(fn (Student $s) => $this->kunciNama($s->name) === $this->kunciNama($nama));

        if ($cocok->count() > 1) {
            // Dua siswa bernama sama di satu kelas: hanya wali kelas yang tahu
            // mana yang dimaksud, jadi barisnya tidak ditebak.
            throw new \RuntimeException(
                'Ada '.$cocok->count().' siswa bernama "'.$nama.'" di kelas ini. '
                .'Isi kolom NIS pada baris ini supaya tidak tertukar.'
            );
        }

        return $cocok->first();
    }

    /**
     * Bentuk nama untuk dicocokkan, bukan untuk disimpan.
     *
     * Sama persis dengan PublicStudentFormController::kunciNama — keduanya
     * menulis ke tabel yang sama, jadi keduanya harus sepakat tentang kapan dua
     * nama berarti orang yang sama.
     */
    private function kunciNama(string $nama): string
    {
        $nama = mb_strtolower(trim($nama));
        $nama = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $nama);

        return trim(preg_replace('/\s+/', ' ', $nama));
    }

    /** Ubah baris mentah menjadi array berkunci nama kolom database. */
    private function petakan(Collection|array $row): array
    {
        $hasil = [];

        foreach ($row as $header => $nilai) {
            $kunci = self::MAP[(string) $header] ?? null;

            if ($kunci === null) {
                continue; // kolom tak dikenal diabaikan, bukan bikin gagal
            }

            $hasil[$kunci] = is_string($nilai) ? trim($nilai) : $nilai;
        }

        return $hasil;
    }

    private function kosongSeluruhnya(array $data): bool
    {
        foreach ($data as $nilai) {
            if ($nilai !== null && $nilai !== '') {
                return false;
            }
        }

        return true;
    }

    /** Normalisasi setiap nilai ke bentuk yang diterima kolomnya. */
    private function bersihkan(array $data): array
    {
        $hasil = [];

        foreach ($data as $kolom => $nilai) {
            $hasil[$kolom] = match ($kolom) {
                'gender' => $this->gender($nilai),
                'agama' => $this->pilihan($nilai, self::AGAMA),
                'golongan_darah' => $this->golonganDarah($nilai),
                'tanggal_lahir' => $this->tanggal($nilai),
                'anak_ke', 'jumlah_saudara', 'tinggi_badan_cm', 'berat_badan_kg', 'tahun_masuk' => $this->bulat($nilai),
                'jarak_rumah_km' => $this->desimal($nilai),
                'penerima_kip', 'penerima_pkh' => $this->boolean($nilai),
                // NIS/NISN dipaksa string: Excel gemar membuang angka nol di depan.
                'nis', 'nisn' => $nilai === null || $nilai === '' ? null : (string) $nilai,
                default => $nilai === '' ? null : $nilai,
            };
        }

        return $hasil;
    }

    private function gender(mixed $v): ?string
    {
        $v = mb_strtolower(trim((string) $v));

        return match (true) {
            in_array($v, ['l', 'laki-laki', 'laki laki', 'lakilaki', 'pria', 'male', 'm'], true) => 'L',
            in_array($v, ['p', 'perempuan', 'wanita', 'female', 'f'], true) => 'P',
            default => null,
        };
    }

    private function pilihan(mixed $v, array $sah): ?string
    {
        $v = trim((string) $v);

        if ($v === '') {
            return null;
        }

        foreach ($sah as $opsi) {
            if (mb_strtolower($opsi) === mb_strtolower($v)) {
                return $opsi;
            }
        }

        // Nilai di luar daftar dibuang, bukan dikirim ke kolom enum yang akan
        // ditolak database dan menggagalkan seluruh import.
        $this->catatan[] = [
            'baris' => $this->baris,
            'nama' => '—',
            'pesan' => "Nilai \"{$v}\" tidak dikenal, kolom itu dikosongkan.",
        ];

        return null;
    }

    private function golonganDarah(mixed $v): ?string
    {
        $v = trim((string) $v);

        if ($v === '' || mb_strtolower($v) === 'tidak tahu' || $v === '-') {
            return $v === '' ? null : 'Tidak Tahu';
        }

        $besar = mb_strtoupper($v);

        return in_array($besar, self::GOLONGAN_DARAH, true) ? $besar : $this->pilihan($v, self::GOLONGAN_DARAH);
    }

    /**
     * Tanggal dari Excel bisa berupa nomor seri, bisa juga teks.
     * Urutan format Indonesia (hari dulu) dicoba lebih awal, karena
     * Carbon::parse akan membaca 01/02/2010 sebagai 2 Januari.
     */
    private function tanggal(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        if (is_numeric($v) && (float) $v > 1) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $teks = trim((string) $v);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d', 'd F Y', 'd M Y'] as $format) {
            try {
                $tanggal = Carbon::createFromFormat($format, $teks);

                if ($tanggal !== false) {
                    return $tanggal->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        $this->catatan[] = [
            'baris' => $this->baris,
            'nama' => '—',
            'pesan' => "Tanggal lahir \"{$teks}\" tidak terbaca. Pakai format YYYY-MM-DD.",
        ];

        return null;
    }

    private function bulat(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }

        $angka = preg_replace('/\D+/', '', (string) $v);

        return $angka === '' ? null : (int) $angka;
    }

    private function desimal(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }

        $angka = str_replace(',', '.', (string) $v);
        $angka = preg_replace('/[^0-9.]/', '', $angka);

        return $angka === '' ? null : (float) $angka;
    }

    private function boolean(mixed $v): ?bool
    {
        if ($v === null || $v === '') {
            return null;
        }

        if (is_bool($v)) {
            return $v;
        }

        $v = mb_strtolower(trim((string) $v));

        return in_array($v, ['ya', 'y', '1', 'true', 'benar', 'iya', 'ada'], true);
    }

    public function ringkasan(): array
    {
        return [
            'dibuat' => $this->dibuat,
            'diperbarui' => $this->diperbarui,
            'dilewati' => $this->dilewati,
            'catatan' => array_slice($this->catatan, 0, 100),
            'catatan_total' => count($this->catatan),
        ];
    }
}
