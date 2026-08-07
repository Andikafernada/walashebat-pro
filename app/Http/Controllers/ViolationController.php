<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Violation;
use App\Models\ViolationType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ViolationController extends Controller
{
    public function index(Classroom $class): View
    {
        $violations = $class->violations()
            ->with(['student', 'type'])->latest('occurred_on')->paginate(30);

        $types = ViolationType::orderBy('name')->get();
        $students = $class->students()->orderBy('name')->get(['id', 'name']);

        return view('violations.index', compact('class', 'violations', 'types', 'students'));
    }

    public function store(Request $request, Classroom $class): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where('class_id', $class->id)],
            // Disaring ke pemilik kelas: aturan exists mentah menembus scope tenant
            // pada model, sehingga jenis pelanggaran milik sekolah lain ikut lolos.
            'violation_type_id' => ['nullable', Rule::exists('violation_types', 'id')->where('user_id', $class->user_id)],
            // Wajib hanya untuk "Pelanggaran Lainnya"; bila jenis dipilih, poin
            // diambil dari jenis tersebut dan input dari klien diabaikan.
            'points' => ['required_without:violation_type_id', 'nullable', 'integer', 'between:-100,100'],
            'note' => ['nullable', 'string', 'max:500'],
            'occurred_on' => ['required', 'date'],
        ]);

        if (! empty($data['violation_type_id'])) {
            $data['points'] = ViolationType::whereKey($data['violation_type_id'])->value('points');
        }

        /*
         * points adalah SELISIH BERTANDA, bukan bobot.
         *
         * Pelanggaran bernilai negatif (mengurangi poin kedisiplinan yang
         * dimulai dari 100), penghargaan bernilai positif. Karena itu di bawah
         * ini cukup dijumlahkan apa adanya.
         */
        $data['points'] = (int) $data['points'];

        DB::transaction(function () use ($class, $data) {
            $class->violations()->create($data + ['user_id' => $class->user_id]);

            // Sesuaikan poin kedisiplinan siswa (poin pelanggaran biasanya negatif).

            /*
             * Lewat relasi kelas, BUKAN Student::whereKey().
             *
             * whereKey() memang sudah dilindungi rantai validasi di atas
             * (student_id wajib milik $class), tapi perlindungan itu ada di
             * tempat lain dan bisa hilang tanpa disadari saat aturan validasi
             * diubah. Menyaring lewat relasi membuat kepemilikan dijaga di
             * baris yang sama dengan operasi tulisnya.
             */
            /*
             * Dulu memakai GREATEST() lewat DB::raw. Itu fungsi khas MySQL yang
             * tidak ada di SQLite, jadi jalur ini mustahil diuji dan pindah
             * database berarti HTTP 500. Baca-terkunci lalu clamp di PHP tetap
             * atomik di dalam transaksi ini, dan berlaku di kedua database.
             */
            $siswa = $class->students()->whereKey($data['student_id'])->lockForUpdate()->first();

            $siswa->update([
                'discipline_points' => max(0, $siswa->discipline_points + $data['points']),
            ]);
        });

        return back()->with('success', 'Pelanggaran dicatat & poin diperbarui.');
    }

    public function destroy(Classroom $class, Violation $violation): RedirectResponse
    {
        /*
         * Pengembalian poin harus MEMBATALKAN yang dilakukan store(): kurangi
         * selisih yang dulu ditambahkan.
         *
         * Versi lama memakai abs() sehingga selalu menambah. Untuk pelanggaran
         * (-5) hasilnya kebetulan benar, tetapi untuk penghargaan (+20) salah
         * arah: menghapus catatan penghargaan justru MENAMBAH 20 poin lagi,
         * sehingga satu catatan yang sudah tidak ada meninggalkan 40 poin.
         *
         * Catatan: pengurangan yang sempat terpotong batas bawah 0 tidak bisa
         * dipulihkan persis karena riwayatnya tidak disimpan. Batas itu sendiri
         * disengaja, jadi selisih pada kasus ekstrem ini diterima.
         */
        DB::transaction(function () use ($class, $violation) {
            // Sama seperti store(): kepemilikan dijaga lewat relasi kelas.
            $siswa = $class->students()->whereKey($violation->student_id)->lockForUpdate()->first();

            $siswa?->update([
                'discipline_points' => max(0, $siswa->discipline_points - (int) $violation->points),
            ]);

            $violation->delete();
        });

        return back()->with('success', 'Catatan pelanggaran dihapus.');
    }
}
