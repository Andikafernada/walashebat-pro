<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Support\PeriodeLaporan;
use App\Support\PoinKerajinan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Peringkat kerajinan (akumulasi kehadiran) per semester dan sertifikat
 * "Siswa Terajin" untuk yang berpoin tertinggi.
 */
class KerajinanController extends Controller
{
    public function index(Classroom $class, Request $request): View
    {
        // Halaman ini soal semester, bukan bulan — beda dari laporan biasa.
        $request->merge(['mode' => $request->input('mode', 'semester')]);
        $periode = PeriodeLaporan::resolve($request);
        $peringkat = PoinKerajinan::peringkat($class, $periode['awal'], $periode['akhir']);

        return view('kerajinan.index', compact('class', 'periode', 'peringkat'));
    }

    public function sertifikat(Classroom $class, Request $request): Response
    {
        $request->merge(['mode' => $request->input('mode', 'semester')]);
        $periode = PeriodeLaporan::resolve($request);
        $peringkat = PoinKerajinan::peringkat($class, $periode['awal'], $periode['akhir']);

        abort_if($peringkat->isEmpty(), 404, 'Belum ada data kehadiran pada periode ini.');

        // Default juara (poin tertinggi); boleh cetak siswa tertentu via ?student_id.
        $pilihId = (int) $request->integer('student_id');
        $baris = $pilihId
            ? $peringkat->firstWhere('student_id', $pilihId)
            : $peringkat->first();

        abort_if(! $baris, 404, 'Siswa tidak ada di peringkat periode ini.');

        $peringkatKe = $peringkat->search(fn ($b) => $b->student_id === $baris->student_id) + 1;

        $pdf = Pdf::loadView('kerajinan.sertifikat', [
            'guru' => $request->user(),
            'nama' => $baris->name,
            'kelas' => $class->name,
            'poin' => (int) $baris->poin,
            'peringkatKe' => $peringkatKe,
            'periode' => $periode,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('sertifikat-kerajinan-'.Str::slug($baris->name).'.pdf');
    }
}
