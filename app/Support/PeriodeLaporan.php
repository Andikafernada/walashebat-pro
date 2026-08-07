<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Penerjemah pilihan periode menjadi rentang tanggal.
 *
 * Dipakai bersama oleh laporan administrasi dan halaman detail siswa. Kalau
 * kedua halaman menafsirkan "Semester 1" dengan caranya masing-masing, angka
 * kehadiran siswa di profilnya bisa berbeda dari angka di laporan yang
 * ditandatangani — dan tidak akan ada yang tahu mana yang benar.
 */
class PeriodeLaporan
{
    /**
     * @return array{awal: Carbon, akhir: Carbon, label: string, mode: string, slug: string, bulan: Carbon, semester: int, tahun: int}
     */
    public static function resolve(Request $request): array
    {
        $data = $request->validate([
            'mode' => ['nullable', Rule::in(['bulan', 'semester', 'rentang'])],
            'bulan' => ['nullable', 'date_format:Y-m'],
            'semester' => ['nullable', 'string'],
            'sub_periode' => ['nullable', 'string'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $mode = $data['mode'] ?? 'bulan';

        $bulan = isset($data['bulan'])
            ? Carbon::createFromFormat('Y-m', $data['bulan'])->startOfMonth()
            : now()->startOfMonth();

        /*
         * Tahun ajaran berjalan, bukan tahun kalender: sebelum Juli kita masih
         * berada di tahun ajaran yang dimulai tahun sebelumnya.
         */
        $tahunDefault = now()->month >= 7 ? now()->year : now()->year - 1;
        $tahun = (int) ($data['tahun'] ?? $tahunDefault);
        $semester = (int) ($data['semester'] ?? (now()->month >= 7 ? 1 : 2));

        if ($mode === 'semester') {
            $sub = (string) ($data['sub_periode'] ?? $data['semester'] ?? '1_penuh');

            switch ($sub) {
                case '1_tengah':
                case '1_pts':
                    $awal = Carbon::create($tahun, 7, 1)->startOfDay();
                    $akhir = Carbon::create($tahun, 9, 30)->endOfDay();
                    $label = sprintf('Tengah Semester 1 (PTS/STS 1) T.A. %d/%d', $tahun, $tahun + 1);
                    $slug = sprintf('pts-1-%d-%d', $tahun, $tahun + 1);
                    $semester = 1;
                    break;

                case '1_akhir':
                case '1_pas':
                    $awal = Carbon::create($tahun, 10, 1)->startOfDay();
                    $akhir = Carbon::create($tahun, 12, 31)->endOfDay();
                    $label = sprintf('Akhir Semester 1 (PAS/SAS 1) T.A. %d/%d', $tahun, $tahun + 1);
                    $slug = sprintf('pas-1-%d-%d', $tahun, $tahun + 1);
                    $semester = 1;
                    break;

                case '2_tengah':
                case '2_pts':
                    $awal = Carbon::create($tahun + 1, 1, 1)->startOfDay();
                    $akhir = Carbon::create($tahun + 1, 3, 31)->endOfDay();
                    $label = sprintf('Tengah Semester 2 (PTS/STS 2) T.A. %d/%d', $tahun, $tahun + 1);
                    $slug = sprintf('pts-2-%d-%d', $tahun, $tahun + 1);
                    $semester = 2;
                    break;

                case '2_akhir':
                case '2_pas':
                    $awal = Carbon::create($tahun + 1, 4, 1)->startOfDay();
                    $akhir = Carbon::create($tahun + 1, 6, 30)->endOfDay();
                    $label = sprintf('Akhir Semester 2 (PAS/SAS 2) T.A. %d/%d', $tahun, $tahun + 1);
                    $slug = sprintf('pas-2-%d-%d', $tahun, $tahun + 1);
                    $semester = 2;
                    break;

                case '2':
                case '2_penuh':
                    $awal = Carbon::create($tahun + 1, 1, 1)->startOfDay();
                    $akhir = Carbon::create($tahun + 1, 6, 30)->endOfDay();
                    $label = sprintf('Semester 2 Penuh T.A. %d/%d', $tahun, $tahun + 1);
                    $slug = sprintf('semester-2-%d-%d', $tahun, $tahun + 1);
                    $semester = 2;
                    break;

                case '1':
                case '1_penuh':
                default:
                    $awal = Carbon::create($tahun, 7, 1)->startOfDay();
                    $akhir = Carbon::create($tahun, 12, 31)->endOfDay();
                    $label = sprintf('Semester 1 Penuh T.A. %d/%d', $tahun, $tahun + 1);
                    $slug = sprintf('semester-1-%d-%d', $tahun, $tahun + 1);
                    $semester = 1;
                    break;
            }
        } elseif ($mode === 'rentang' && isset($data['dari'], $data['sampai'])) {
            $awal = Carbon::parse($data['dari'])->startOfDay();
            $akhir = Carbon::parse($data['sampai'])->endOfDay();
            $label = $awal->translatedFormat('d M Y').' – '.$akhir->translatedFormat('d M Y');
            $slug = $awal->format('Ymd').'-'.$akhir->format('Ymd');
        } else {
            $mode = 'bulan';
            $awal = $bulan->copy()->startOfMonth();
            $akhir = $bulan->copy()->endOfMonth();
            $label = $bulan->translatedFormat('F Y');
            $slug = $bulan->format('Y-m');
        }

        $sub_periode = $sub ?? '1_penuh';
        return compact('awal', 'akhir', 'label', 'mode', 'slug', 'bulan', 'semester', 'tahun', 'sub_periode');
    }
}
