<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Http\Requests\StudentRequest;
use App\Imports\StudentsImport;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\StudentProfileBuilder;
use App\Support\PeriodeLaporan;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{

    public function qrCards(Classroom $class): View
    {
        $students = $class->students()->where('is_active', true)->orderBy('name')->get();
        return view('students.qr_cards', compact('class', 'students'));
    }

    private const URUTAN = [
        'nama' => 'name',
        'nis' => 'nis',
        'poin' => 'discipline_points',
    ];

    public function index(Request $request, Classroom $class): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $urut = array_key_exists((string) $request->query('urut'), self::URUTAN)
            ? (string) $request->query('urut')
            : 'nama';
        $arah = $request->query('arah') === 'desc' ? 'desc' : 'asc';
        $per = (int) $request->query('per', 25);
        $per = in_array($per, [25, 50, 100], true) ? $per : 25;

        $students = $class->students()
            ->when($cari !== '', function ($query) use ($cari) {
                $query->where(function ($w) use ($cari) {
                    $w->where('name', 'like', "%{$cari}%")
                        ->orWhere('nis', 'like', "%{$cari}%")
                        ->orWhere('nisn', 'like', "%{$cari}%")
                        ->orWhere('parent_phone', 'like', "%{$cari}%");
                });
            })
            ->orderBy(self::URUTAN[$urut], $arah)
            ->paginate($per)
            ->withQueryString();

        // partials/class-nav menampilkan lencana jumlah siswa dari students_count.
        // Tanpa loadCount nilainya null dan lencananya hilang begitu saja.
        $class->loadCount('students');

        return view('students.index', [
            'classroom' => $class,
            'students' => $students,
            'cari' => $cari,
            'urut' => $urut,
            'arah' => $arah,
            'per' => $per,
            'totalKelas' => $class->students_count,
        ]);
    }

    /**
     * Profil lengkap satu siswa.
     *
     * Sebelumnya rute ini sengaja dimatikan (`->except(['show'])`), sehingga
     * untuk mengetahui keadaan satu anak wali kelas harus membuka empat menu
     * terpisah lalu menghitung sendiri. Padahal momen yang paling butuh data
     * itu justru yang paling tidak memberi waktu — orang tua sedang menelepon,
     * atau BK sedang menunggu di depan meja.
     */
    public function show(Request $request, Classroom $class, Student $student): View
    {
        abort_if($student->class_id !== $class->id, 404);

        $periode = PeriodeLaporan::resolve($request);
        $profil = app(StudentProfileBuilder::class)
            ->build($student, $periode['awal'], $periode['akhir'], $periode['semester']);

        return view('students.show', $profil + [
            'classroom' => $class,
            'periode' => $periode,
        ]);
    }

    /** Profil siswa sebagai PDF, untuk arsip atau diserahkan ke BK/orang tua. */
    public function showPdf(Request $request, Classroom $class, Student $student): Response
    {
        abort_if($student->class_id !== $class->id, 404);

        $periode = PeriodeLaporan::resolve($request);
        $profil = app(StudentProfileBuilder::class)
            ->build($student, $periode['awal'], $periode['akhir'], $periode['semester']);

        $pdf = Pdf::loadView('reports.pdf.siswa', $profil + [
            'classroom' => $class,
            'periode' => $periode,
            'guru' => $request->user(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download(sprintf(
            'Profil-%s-%s.pdf',
            str($student->name)->slug(),
            $periode['slug']
        ));
    }

    public function create(Classroom $class): View
    {
        return view('students.create', ['classroom' => $class]);
    }

    public function store(StudentRequest $request, Classroom $class): RedirectResponse
    {
        $data = $this->tanpaFoto($request->validated());

        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('students/photos', 'public');
        }

        // Set default temporary password (hashed)
        // Siswa harus ganti password via OTP untuk pertama kali
        $defaultPassword = 'Walikelas' . date('Y'); // e.g., "Walikelas2026"
        $data['password'] = Hash::make($defaultPassword);
        $data['must_change_password'] = true;

        $student = $class->students()->create($data + ['user_id' => $class->user_id]);

        return redirect()->route('classes.students.index', $class)
            ->with('success', "Siswa ditambahkan. Password awal: {$defaultPassword}");
    }

    public function edit(Classroom $class, Student $student): View
    {
        return view('students.edit', ['classroom' => $class, 'student' => $student]);
    }

    public function update(StudentRequest $request, Classroom $class, Student $student): RedirectResponse
    {
        $data = $this->tanpaFoto($request->validated());

        if ($request->hasFile('foto')) {
            // Buang foto lama supaya penyimpanan tidak menumpuk berkas mati.
            if ($student->foto_path) {
                Storage::disk('public')->delete($student->foto_path);
            }

            $data['foto_path'] = $request->file('foto')->store('students/photos', 'public');
        }

        $student->update($data);

        return redirect()->route('classes.students.index', $class)
            ->with('success', 'Data siswa diperbarui.');
    }

    public function destroy(Classroom $class, Student $student): RedirectResponse
    {
        $student->delete(); // soft delete

        return redirect()->route('classes.students.index', $class)
            ->with('success', 'Siswa dipindahkan ke arsip.');
    }

    /**
     * Mengosongkan kelas: seluruh siswa aktif dipindahkan ke arsip sekaligus.
     *
     * Penghapusannya soft delete, persis seperti destroy() satuan, sehingga
     * salah tekan masih bisa dibatalkan dari halaman Arsip dan seluruh riwayat
     * absensi, nilai, serta pelanggarannya tetap utuh. Impor ulang dengan NIS
     * yang sama tetap berjalan karena keunikan NIS ditegakkan di StudentRequest
     * hanya atas baris yang belum dihapus — bukan oleh indeks unik di basis
     * data (lihat migrasi create_students_table).
     *
     * Konfirmasinya menuntut nama kelas diketik ulang, dan diperiksa di SERVER,
     * bukan hanya di browser. Ini satu-satunya tombol di aplikasi yang menghapus
     * puluhan baris sekaligus; dialog "Yakin?" biasa terlalu mudah tertekan
     * tanpa sengaja di layar sentuh, dan pemiliknya baru sadar setelah daftar
     * siswanya kosong.
     */
    public function destroyAll(Request $request, Classroom $class): RedirectResponse
    {
        $data = $request->validate([
            'nama_kelas' => ['required', 'string'],
        ], [
            'nama_kelas.required' => 'Ketik nama kelas untuk mengonfirmasi.',
        ]);

        // Dibandingkan setelah dirapikan: spasi berlebih dan beda huruf besar
        // -kecil bukan tanda salah maksud, dan menolaknya hanya membuat guru
        // mengetik ulang berkali-kali tanpa menambah satu pun pengaman.
        $diketik = Str::squish($data['nama_kelas']);

        if (Str::lower($diketik) !== Str::lower(Str::squish($class->name))) {
            return back()
                ->withErrors(['nama_kelas' => 'Nama kelas tidak cocok. Ketik persis: '.$class->name])
                ->withInput();
        }

        $jumlah = $class->students()->count();

        if ($jumlah === 0) {
            return redirect()->route('classes.students.index', $class)
                ->with('info', 'Tidak ada siswa untuk dihapus di kelas ini.');
        }

        // Satu query, bukan perulangan ->delete() per siswa: Student tidak punya
        // observer maupun event deleting/deleted, jadi tidak ada yang terlewat.
        $class->students()->delete();

        return redirect()->route('classes.students.index', $class)
            ->with('success', "{$jumlah} siswa dipindahkan ke arsip. Masih bisa dipulihkan dari halaman Arsip.");
    }

    public function trashed(Classroom $class): View
    {
        $students = $class->students()->onlyTrashed()->orderBy('name')->paginate(25);

        return view('students.trashed', ['classroom' => $class, 'students' => $students]);
    }

    public function restore(Classroom $class, int $student): RedirectResponse
    {
        $model = $class->students()->onlyTrashed()->findOrFail($student);
        $model->restore();

        return redirect()->route('classes.students.trashed', $class)
            ->with('success', 'Siswa dipulihkan.');
    }

    /*
    |--------------------------------------------------------------------------
    | Excel
    |--------------------------------------------------------------------------
    */

    public function importForm(Classroom $class): View
    {
        return view('students.import', ['classroom' => $class]);
    }

    /**
     * Unggah berkas lalu tampilkan hasilnya per baris.
     *
     * Tidak memakai WithValidation di kelas import, jadi satu baris cacat tidak
     * menggagalkan seluruh berkas. Kegagalan tingkat berkas (format rusak,
     * bukan spreadsheet) ditangkap di sini dan dilaporkan sebagai pesan biasa,
     * bukan halaman error 500.
     */
    public function import(Request $request, Classroom $class): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [], ['file' => 'berkas']);

        $import = new StudentsImport($class);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'file' => 'Berkas tidak bisa dibaca. Pastikan formatnya .xlsx, .xls, atau .csv dan judul kolomnya masih utuh.',
            ]);
        }

        $hasil = $import->ringkasan();

        $ringkas = collect([
            $hasil['dibuat'] > 0 ? "{$hasil['dibuat']} siswa baru" : null,
            $hasil['diperbarui'] > 0 ? "{$hasil['diperbarui']} diperbarui" : null,
            $hasil['dilewati'] > 0 ? "{$hasil['dilewati']} dilewati" : null,
        ])->filter()->implode(' · ');

        return redirect()->route('classes.students.index', $class)
            ->with('success', $ringkas !== '' ? "Import selesai: {$ringkas}." : 'Tidak ada baris yang terbaca dari berkas itu.')
            ->with('import_catatan', $hasil['catatan'])
            ->with('import_catatan_total', $hasil['catatan_total']);
    }

    public function export(Classroom $class): BinaryFileResponse
    {
        $nama = 'Data-Siswa-'.Str::slug($class->name).'-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new StudentsExport($class), $nama);
    }

    public function template(Classroom $class): BinaryFileResponse
    {
        return Excel::download(
            new StudentsExport($class, templateSaja: true),
            'Template-Import-Siswa.xlsx'
        );
    }

    /** Berkas foto ditangani terpisah, bukan lewat mass assignment. */
    private function tanpaFoto(array $data): array
    {
        unset($data['foto']);

        return $data;
    }
}
