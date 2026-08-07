<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Support\PeriodeLaporan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Jurnal mengajar guru mapel.
 *
 * Dokumen administratif yang wajib diisi guru mapel di sekolah: tanggal,
 * kelas, mata pelajaran, materi yang diajarkan, dan jumlah siswa hadir/absen.
 *
 * TIDAK punya tabel sendiri, dan itu disengaja. Seluruh isinya sudah terekam
 * saat guru mengisi presensi — tanggal, mapel, materi, dan status tiap siswa.
 * Menyimpannya ulang di tempat kedua hanya melahirkan dua sumber kebenaran
 * yang bisa berbeda tanpa ada yang menyadarinya; yang dilakukan di sini
 * hanyalah menyusun ulang data yang sama menjadi bentuk jurnal.
 *
 * Satu-satunya yang bisa disunting dari sini adalah MATERI: guru kerap mengisi
 * presensi dengan cepat di kelas lalu melengkapi materinya setelah jam
 * mengajar selesai.
 */
class JurnalMengajarController extends Controller
{
    public function index(Request $request, Classroom $class): View
    {
        $periode = PeriodeLaporan::resolve($request);

        $mapelDiampu = $class->mapelDiampu();
        $mapelDipilih = $this->mapelDipilih($request, $mapelDiampu);

        $sesi = $class->attendanceSessions()
            ->whereBetween('session_date', [
                $periode['awal']->copy()->startOfDay(),
                $periode['akhir']->copy()->endOfDay(),
            ])
            ->where('status', '!=', 'cancelled')
            ->when(filled($mapelDipilih), fn ($q) => $q->where('mapel', $mapelDipilih))
            ->withCount([
                'attendances as jumlah_hadir' => fn ($q) => $q->where('status', 'hadir'),
                'attendances as jumlah_terlambat' => fn ($q) => $q->where('status', 'terlambat'),
                'attendances as jumlah_sakit' => fn ($q) => $q->where('status', 'sakit'),
                'attendances as jumlah_izin' => fn ($q) => $q->where('status', 'izin'),
                'attendances as jumlah_alfa' => fn ($q) => $q->where('status', 'alfa'),
            ])
            /*
             * Nama siswa yang TIDAK hadir ikut dimuat. Jurnal yang hanya
             * menyebut angka "3 tidak hadir" tidak berguna saat guru ditanya
             * siapa saja — dan mencarinya ulang di menu absensi per tanggal
             * adalah pekerjaan yang justru ingin dihindari jurnal ini.
             */
            ->with(['attendances' => fn ($q) => $q->where('status', '!=', 'hadir')
                ->with('student:id,name')])
            ->orderByDesc('session_date')
            ->orderByDesc('sequence')
            ->get();

        return view('jurnal.index', [
            'classroom' => $class,
            'periode' => $periode,
            'sesi' => $sesi,
            'mapelDiampu' => $mapelDiampu,
            'mapelDipilih' => $mapelDipilih,
        ]);
    }

    /**
     * Lengkapi materi setelah jam mengajar selesai.
     *
     * Sengaja hanya materi. Tanggal, mapel, dan kehadiran adalah catatan
     * kejadian — mengubahnya dari halaman jurnal akan mengubah data presensi
     * tanpa jejak, sementara koreksi absensi sudah punya jalurnya sendiri yang
     * mencatat revisi.
     */
    public function updateMateri(Request $request, Classroom $class, AttendanceSession $attendanceSession): RedirectResponse
    {
        abort_unless($attendanceSession->class_id === $class->id, 404);

        $data = $request->validate([
            'materi' => ['nullable', 'string', 'max:500'],
        ]);

        $attendanceSession->update(['materi' => $data['materi'] ?? null]);

        return back()->with('success', 'Materi jurnal berhasil disimpan.');
    }

    /**
     * Mapel yang sedang ditampilkan.
     *
     * Hanya menerima mapel yang benar-benar diampu di kelas ini; nilai lain
     * diabaikan dan jatuh ke "semua". Tanpa penjagaan ini, parameter URL
     * sembarangan menghasilkan jurnal kosong yang terlihat seperti guru belum
     * pernah mengajar.
     *
     * @param  array<int, string>  $mapelDiampu
     */
    private function mapelDipilih(Request $request, array $mapelDiampu): ?string
    {
        $diminta = trim((string) $request->query('mapel', ''));

        if ($diminta === '' || ! in_array($diminta, $mapelDiampu, true)) {
            // Satu mapel saja: tidak perlu memilih, langsung tampilkan.
            return count($mapelDiampu) === 1 ? $mapelDiampu[0] : null;
        }

        return $diminta;
    }
}
