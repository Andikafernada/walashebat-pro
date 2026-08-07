<?php

namespace App\Exports;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Satu kelas untuk dua keperluan:
 *
 * - Ekspor data kelas yang ada, supaya guru bisa mengedit di Excel lalu
 *   mengunggah kembali. Alur pulang-balik ini jauh lebih enak dipakai
 *   daripada template kosong, karena guru melihat data yang sudah ada.
 * - Template kosong berisi satu baris contoh, untuk kelas yang masih nol siswa.
 *
 * Judul kolom di sini HARUS cocok dengan kunci di StudentsImport::MAP setelah
 * di-slug oleh WithHeadingRow. "HP Ortu" menjadi hp_ortu, dan seterusnya.
 */
class StudentsExport implements FromQuery, WithColumnFormatting, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithChunkReading, WithStyles, WithTitle
{
    private const KOLOM_TERAKHIR = 'AH';

    /** Kelas ajar: NIS + Nama saja, jadi kolom terakhirnya B. */
    private const KOLOM_TERAKHIR_RINGKAS = 'B';

    private const BARIS_VALIDASI = 300;

    /**
     * Guru mapel hanya memegang NIS dan nama.
     *
     * Menyerahkan template 34 kolom — lengkap dengan Nama Ayah, Pekerjaan Ibu,
     * dan Penerima KIP — kepada guru yang cuma mengajar di kelas itu bukan
     * sekadar merepotkan: itu mengundang pengumpulan data yang bukan haknya,
     * lalu menciptakan salinan kedua biodata yang menyimpang dari milik wali
     * kelas aslinya. Bentuk berkasnya ikut menentukan apa yang orang isi.
     */
    private readonly bool $ringkas;

    public function __construct(
        private readonly Classroom $classroom,
        private readonly bool $templateSaja = false,
    ) {
        $this->ringkas = $classroom->kelasAjar();
    }

    private function kolomTerakhir(): string
    {
        return $this->ringkas ? self::KOLOM_TERAKHIR_RINGKAS : self::KOLOM_TERAKHIR;
    }

    public function title(): string
    {
        return 'Data Siswa';
    }

    /**
     * FromQuery, BUKAN FromCollection.
     *
     * FromCollection memanggil ->get() lebih dulu, sehingga seluruh siswa
     * beserta 34 kolomnya masuk ke memori PHP sekaligus sebelum satu baris pun
     * ditulis ke berkas. Untuk satu kelas itu tidak terasa, tapi memori yang
     * dipakai tumbuh sebanding jumlah baris — dan beberapa ekspor yang
     * berjalan bersamaan bisa menghabiskan jatah memori PHP.
     *
     * FromQuery membiarkan paket Excel mengambil data per potongan sesuai
     * chunkSize(), jadi pemakaian memorinya tetap datar berapa pun jumlah
     * siswanya.
     */
    public function query(): Builder
    {
        /*
         * Mode template: kelas yang belum punya siswa tetap harus menghasilkan
         * berkas berisi SATU baris contoh. Baris itu tidak ada di database,
         * jadi tidak bisa datang dari query — dipakai query yang pasti kosong,
         * lalu barisnya disisipkan lewat event AfterSheet.
         */
        // getQuery() mengubah relasi HasMany menjadi Eloquent Builder,
        // tipe yang diminta antarmuka FromQuery.
        if ($this->templateSaja) {
            return $this->classroom->students()->whereRaw('1 = 0')->getQuery();
        }

        return $this->classroom->students()->orderBy('name')->getQuery();
    }

    /**
     * Sengaja disamakan dengan StudentsImport::chunkSize(). Alur ekspor dan
     * impor adalah pulang-balik untuk berkas yang sama; menyetel keduanya
     * berbeda hanya membuat perilaku memorinya sulit ditebak.
     */
    public function chunkSize(): int
    {
        return 200;
    }

    public function headings(): array
    {
        if ($this->ringkas) {
            return ['NIS', 'Nama'];
        }

        return [
            'NIS', 'NISN', 'Nama', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir',
            'Agama', 'Anak Ke', 'Jumlah Saudara', 'Golongan Darah', 'Tinggi Badan', 'Berat Badan',
            'Alamat', 'RT RW', 'Kelurahan', 'Kecamatan', 'HP Siswa', 'HP Ortu',
            'Jarak Rumah KM', 'Moda Transportasi',
            'Nama Ayah', 'Pekerjaan Ayah', 'Nama Ibu', 'Pekerjaan Ibu',
            'Nama Wali', 'Pekerjaan Wali', 'Alamat Ortu',
            'Asal Sekolah', 'Tahun Masuk', 'Kompetensi Keahlian',
            'Hobi', 'Cita Cita', 'Penerima KIP', 'Penerima PKH',
        ];
    }

    /** @param  Student  $siswa */
    public function map($siswa): array
    {
        if ($this->ringkas) {
            return [$siswa->nis, $siswa->name];
        }

        return [
            $siswa->nis,
            $siswa->nisn,
            $siswa->name,
            $siswa->gender,
            $siswa->tempat_lahir,
            $siswa->tanggal_lahir?->format('Y-m-d'),
            $siswa->agama,
            $siswa->anak_ke,
            $siswa->jumlah_saudara,
            $siswa->golongan_darah,
            $siswa->tinggi_badan_cm,
            $siswa->berat_badan_kg,
            $siswa->address,
            $siswa->rt_rw,
            $siswa->kelurahan,
            $siswa->kecamatan,
            $siswa->phone,
            $siswa->parent_phone,
            $siswa->jarak_rumah_km,
            $siswa->moda_transportasi,
            $siswa->nama_ayah,
            $siswa->pekerjaan_ayah,
            $siswa->nama_ibu,
            $siswa->pekerjaan_ibu,
            $siswa->nama_wali,
            $siswa->pekerjaan_wali,
            $siswa->alamat_ortu,
            $siswa->asal_sekolah,
            $siswa->tahun_masuk,
            $siswa->kompetensi_keahlian,
            $siswa->hobi,
            $siswa->cita_cita,
            $siswa->penerima_kip ? 'Ya' : 'Tidak',
            $siswa->penerima_pkh ? 'Ya' : 'Tidak',
        ];
    }

    /**
     * Kolom yang dipaksa teks. Tanpa ini Excel membuang angka nol di depan NIS
     * dan mengubah 081234567890 menjadi notasi ilmiah.
     */
    public function columnFormats(): array
    {
        if ($this->ringkas) {
            // NIS tetap teks agar angka nol di depan tidak hilang.
            return ['A' => NumberFormat::FORMAT_TEXT];
        }

        return [
            'A' => NumberFormat::FORMAT_TEXT, // NIS
            'B' => NumberFormat::FORMAT_TEXT, // NISN
            'F' => NumberFormat::FORMAT_TEXT, // Tanggal Lahir, ditulis sebagai teks YYYY-MM-DD
            'Q' => NumberFormat::FORMAT_TEXT, // HP Siswa
            'R' => NumberFormat::FORMAT_TEXT, // HP Ortu
            'N' => NumberFormat::FORMAT_TEXT, // RT RW, mis. 003/007
        ];
    }

    public function columnWidths(): array
    {
        if ($this->ringkas) {
            return ['A' => 16, 'B' => 32];
        }

        return [
            'A' => 14, 'B' => 16, 'C' => 28, 'D' => 14, 'E' => 18, 'F' => 15,
            'G' => 12, 'H' => 9, 'I' => 15, 'J' => 15, 'K' => 12, 'L' => 12,
            'M' => 34, 'N' => 11, 'O' => 18, 'P' => 18, 'Q' => 18, 'R' => 18,
            'S' => 15, 'T' => 20, 'U' => 22, 'V' => 20, 'W' => 22, 'X' => 20,
            'Y' => 22, 'Z' => 20, 'AA' => 34, 'AB' => 24, 'AC' => 13, 'AD' => 24,
            'AE' => 18, 'AF' => 18, 'AG' => 14, 'AH' => 14,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:'.$this->kolomTerakhir().'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                /*
                 * Baris contoh untuk mode template ditulis di sini, bukan lewat
                 * query: barisnya tidak ada di database, sementara FromQuery
                 * hanya bisa mengalirkan baris yang benar-benar tersimpan.
                 */
                if ($this->templateSaja) {
                    $sheet->fromArray($this->map($this->barisContoh()), null, 'A2');
                }

                // Kolom kunci tetap terlihat saat digeser ke kanan.
                $sheet->freezePane($this->ringkas ? 'C2' : 'D2');
                $sheet->setAutoFilter('A1:'.$this->kolomTerakhir().'1');

                if (! $this->ringkas) {
                    $this->dropdown($sheet, 'D', '"L,P"', 'Isi L untuk laki-laki atau P untuk perempuan.');
                    $this->dropdown($sheet, 'G', '"Islam,Kristen,Katolik,Hindu,Buddha,Konghucu,Lainnya"', 'Pilih dari daftar agama.');
                    $this->dropdown($sheet, 'J', '"A,B,AB,O,Tidak Tahu"', 'Pilih golongan darah.');
                    $this->dropdown($sheet, 'AG', '"Ya,Tidak"', 'Ya bila siswa penerima KIP.');
                    $this->dropdown($sheet, 'AH', '"Ya,Tidak"', 'Ya bila keluarga penerima PKH.');
                }

                $catatanNis = "NIS dipakai untuk mencocokkan siswa saat berkas diunggah kembali.\n".
                    "Jangan diubah kecuali memang salah.\n".
                    'Kolom ini berformat teks agar angka nol di depan tidak hilang.';

                $catatanNama = "Wajib diisi untuk siswa baru.\n".
                    'Kalau NIS kosong, nama inilah yang dipakai untuk mencocokkan.';

                /*
                 * Pada mode ringkas kolomnya bergeser: Nama ada di B, bukan C.
                 * Menempelkan catatan ke sel yang salah membuat petunjuknya
                 * muncul di kolom kosong dan tidak terlihat di kolom yang
                 * benar-benar perlu dijelaskan.
                 */
                $catatan = $this->ringkas
                    ? ['A1' => $catatanNis, 'B1' => $catatanNama]
                    : [
                        'A1' => $catatanNis,
                        'C1' => $catatanNama,
                        'D1' => 'Isi L untuk laki-laki atau P untuk perempuan.',
                        'F1' => "Format tanggal: YYYY-MM-DD\nContoh: 2010-08-17",
                        'R1' => "Nomor ini yang menerima notifikasi absensi via WhatsApp.\n".
                            'Boleh ditulis 08xxx, nanti diubah sendiri menjadi 628xxx.',
                    ];

                foreach ($catatan as $sel => $teks) {
                    $komentar = $sheet->getComment($sel);
                    $komentar->setWidth('320px');
                    $komentar->setHeight('110px');
                    $komentar->getText()->createTextRun($teks);
                }
            },
        ];
    }

    private function dropdown(Worksheet $sheet, string $kolom, string $daftar, string $petunjuk): void
    {
        $rentang = $kolom.'2:'.$kolom.self::BARIS_VALIDASI;

        $validasi = $sheet->getCell($kolom.'2')->getDataValidation();
        $validasi->setType(DataValidation::TYPE_LIST);
        $validasi->setErrorStyle(DataValidation::STYLE_WARNING);
        $validasi->setAllowBlank(true);
        $validasi->setShowDropDown(true);
        $validasi->setShowInputMessage(true);
        $validasi->setShowErrorMessage(true);
        $validasi->setPromptTitle('Petunjuk');
        $validasi->setPrompt($petunjuk);
        $validasi->setErrorTitle('Nilai di luar daftar');
        $validasi->setError('Sebaiknya pilih dari daftar. Nilai lain akan dikosongkan saat diimpor.');
        $validasi->setFormula1($daftar);
        $validasi->setSqref($rentang);
    }

    private function barisContoh(): Student
    {
        return new Student([
            'nis' => '2024001',
            'nisn' => '0098765432',
            'name' => 'Ahmad Fauzi',
            'gender' => 'L',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2010-08-17',
            'agama' => 'Islam',
            'anak_ke' => 2,
            'jumlah_saudara' => 3,
            'golongan_darah' => 'O',
            'tinggi_badan_cm' => 158,
            'berat_badan_kg' => 47,
            'address' => 'Jl. Merdeka No. 12',
            'rt_rw' => '003/007',
            'kelurahan' => 'Cibeunying',
            'kecamatan' => 'Cimenyan',
            'phone' => '081234567890',
            'parent_phone' => '081298765432',
            'jarak_rumah_km' => 2.5,
            'moda_transportasi' => 'Sepeda motor',
            'nama_ayah' => 'Bambang Fauzi',
            'pekerjaan_ayah' => 'Wiraswasta',
            'nama_ibu' => 'Siti Aminah',
            'pekerjaan_ibu' => 'Ibu rumah tangga',
            'nama_wali' => '',
            'pekerjaan_wali' => '',
            'alamat_ortu' => 'Jl. Merdeka No. 12',
            'asal_sekolah' => 'SMPN 3 Bandung',
            'tahun_masuk' => 2024,
            'kompetensi_keahlian' => 'Teknik Komputer dan Jaringan',
            'hobi' => 'Futsal',
            'cita_cita' => 'Teknisi jaringan',
            'penerima_kip' => false,
            'penerima_pkh' => false,
        ]);
    }
}
