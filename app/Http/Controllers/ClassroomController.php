<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassroomRequest;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(): View
    {
        /*
         * Banyak wali kelas di Indonesia sekaligus mengajar sebagai guru mapel,
         * sehingga daftar ini bercampur: satu kelas perwalian di antara
         * beberapa kelas yang hanya diajar. Keduanya menuntut pekerjaan yang
         * sangat berbeda — dan sebelumnya tampil dengan kartu yang identik.
         */
        $jenis = in_array(request('jenis'), [Classroom::JENIS_PERWALIAN, Classroom::JENIS_AJAR], true)
            ? request('jenis')
            : null;

        $jumlahPerJenis = Classroom::selectRaw('jenis, COUNT(*) as total')
            ->groupBy('jenis')
            ->pluck('total', 'jenis');

        $classes = Classroom::withCount('students')
            ->when($jenis, fn ($q) => $q->where('jenis', $jenis))
            /*
             * Di tab "Semua Kelas", kelas perwalian disematkan di paling atas.
             *
             * Guru yang mengampu tujuh kelas mapel hanya punya satu kelas yang
             * benar-benar ia walikelasi, dan justru kelas itulah yang menuntut
             * pekerjaan paling banyak — biodata, kas, pelanggaran, laporan.
             * Diurutkan menurut tanggal buat saja, kelas itu bisa terdampar di
             * tengah daftar atau bahkan halaman kedua, dan setiap kali dicari
             * ulang. Menyematkannya juga menjamin ia selalu ada di halaman
             * pertama berapa pun jumlah kelas ajarnya.
             *
             * Hanya berlaku saat tidak ada saringan: pada tab Perwalian semua
             * kartu berjenis sama, sehingga urutannya tidak menyampaikan apa pun.
             */
            ->when(! $jenis, fn ($q) => $q->orderByRaw(
                'CASE WHEN jenis = ? THEN 0 ELSE 1 END',
                [Classroom::JENIS_PERWALIAN],
            ))
            ->latest()
            ->paginate(12)
            // Tanpa ini, pindah halaman membuang saringan jenis yang sedang aktif.
            ->withQueryString();

        foreach ($classes as $c) {
            $sesiHariIni = \App\Models\AttendanceSession::where('class_id', $c->id)
                ->whereDate('session_date', now()->toDateString())
                ->get();

            /*
             * Kelas ajar bisa punya lebih dari satu sesi dalam sehari — satu
             * per mapel yang diampu. Mengambil ->first() saja melaporkan
             * "sudah absen" padahal mapel kedua belum disentuh.
             */
            $c->today_session_count = $sesiHariIni->count();

            if ($sesiHariIni->isNotEmpty()) {
                $hadir = \App\Models\Attendance::whereIn('attendance_session_id', $sesiHariIni->pluck('id'))
                    ->whereIn('status', ['hadir', 'terlambat'])
                    ->count();
                $c->recent_attendance_text = $hadir.'/'.($c->students_count * $sesiHariIni->count()).' Hadir';
                $c->recent_attendance_pct = $c->students_count > 0
                    ? round($hadir / ($c->students_count * $sesiHariIni->count()) * 100)
                    : 0;
                $c->has_today_session = true;
            } else {
                $c->recent_attendance_text = 'Belum Absen';
                $c->recent_attendance_pct = 0;
                $c->has_today_session = false;
            }

            // Pelanggaran adalah urusan wali kelas; pada kelas ajar tidak ditampilkan.
            $c->violation_count = $c->kelasAjar()
                ? 0
                : \App\Models\Violation::where('class_id', $c->id)->count();
        }

        return view('classes.index', [
            'classes' => $classes,
            'jenisDipilih' => $jenis,
            'jumlahPerwalian' => (int) ($jumlahPerJenis[Classroom::JENIS_PERWALIAN] ?? 0),
            'jumlahAjar' => (int) ($jumlahPerJenis[Classroom::JENIS_AJAR] ?? 0),
        ]);
    }

    public function create(): View
    {
        return view('classes.create');
    }

    public function store(ClassroomRequest $request): RedirectResponse
    {
        $classroom = Classroom::create($request->validated());

        return redirect()->route('classes.show', $classroom)
            ->with('success', 'Kelas berhasil dibuat.');
    }

    public function show(Classroom $class): View
    {
        $class->load(['students' => fn ($q) => $q->orderBy('name')]);

        // Quick stats for Bento Grid
        $totalStudents = $class->students->count();
        $activeClasses = Classroom::where('is_active', true)->count();
        $todaySession = \App\Models\AttendanceSession::where('class_id', $class->id)
            ->whereDate('session_date', now()->toDateString())
            ->first();

        $todayAttendance = null;
        if ($todaySession) {
            $attendances = $todaySession->attendances;
            $todayAttendance = [
                'total' => $attendances->count(),
                'hadir' => $attendances->whereIn('status', ['hadir', 'terlambat'])->count(),
                'percentage' => $totalStudents > 0 ? round(($attendances->whereIn('status', ['hadir', 'terlambat'])->count() / $totalStudents) * 100) : 0,
            ];
        }

        return view('classes.show', [
            'classroom' => $class,
            'totalStudents' => $totalStudents,
            'activeClasses' => $activeClasses,
            'todaySession' => $todaySession,
            'todayAttendance' => $todayAttendance,
        ]);
    }

    public function edit(Classroom $class): View
    {
        return view('classes.edit', ['classroom' => $class]);
    }

    public function update(ClassroomRequest $request, Classroom $class): RedirectResponse
    {
        $class->update($request->validated());

        return redirect()->route('classes.show', $class)
            ->with('success', 'Kelas diperbarui.');
    }

    public function destroy(Classroom $class): RedirectResponse
    {
        $class->delete();

        return redirect()->route('classes.index')
            ->with('success', 'Kelas dipindahkan ke arsip. Masih bisa dipulihkan.');
    }

    /** Daftar kelas yang diarsipkan. */
    public function trashed(): View
    {
        $classes = Classroom::onlyTrashed()->latest('deleted_at')->paginate(12);

        return view('classes.trashed', compact('classes'));
    }

    public function restore(int $class): RedirectResponse
    {
        $model = Classroom::onlyTrashed()->findOrFail($class);
        $model->restore();

        return redirect()->route('classes.trashed')
            ->with('success', 'Kelas dipulihkan beserta siswanya.');
    }

    public function forceDelete(int $class): RedirectResponse
    {
        $model = Classroom::onlyTrashed()->findOrFail($class);
        $model->forceDelete();

        return redirect()->route('classes.trashed')
            ->with('success', 'Kelas dihapus permanen.');
    }
}
