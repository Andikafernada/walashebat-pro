<?php

namespace App\Http\Controllers;

use App\Models\CashBook;
use App\Models\Classroom;
use App\Support\PeriodeLaporan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CashBookController extends Controller
{
    public function index(Classroom $class): View
    {
        // Tanpa eager load, daftar kas memicu satu query per baris hanya untuk
        // menampilkan nama siswa pembayarnya.
        $entries = $class->cashBooks()
            ->with('student:id,name')
            ->orderByDesc('transaction_date')->orderByDesc('id')->paginate(30);

        $balance = $this->currentBalance($class);

        return view('cashbook.index', ['classroom' => $class, 'entries' => $entries, 'balance' => $balance]);
    }


    /**
     * Siapa yang sudah menyetor kas dan siapa yang belum.
     *
     * Buku kas berbentuk buku besar: urut waktu, bercampur pemasukan dan
     * pengeluaran. Bentuk itu tepat untuk mempertanggungjawabkan saldo, tetapi
     * tidak bisa menjawab pertanyaan yang justru paling sering diajukan wali
     * kelas — "siapa yang belum bayar?" — karena jawabannya tersebar di puluhan
     * baris dan harus dijumlahkan sendiri per anak.
     *
     * "Belum" di sini berarti TIDAK ADA setoran atas namanya pada periode ini,
     * bukan "kurang dari sekian". Buku kas tidak menyimpan besaran iuran yang
     * seharusnya, jadi menghitung kekurangan akan menuntut angka yang belum
     * pernah ada. Untuk kas bulanan yang lazim, "nol setoran bulan ini" sudah
     * menjawab pertanyaannya.
     *
     * Pengeluaran (type=out) tidak ikut dihitung sekalipun bertanda siswa:
     * uang yang keluar bukan setoran, dan menjumlahkannya akan membuat anak
     * yang uangnya dipakai untuk keperluan kelas terlihat lebih sedikit
     * membayar daripada yang sebenarnya.
     */
    public function perSiswa(Request $request, Classroom $class): View
    {
        $periode = PeriodeLaporan::resolve($request);

        $setoran = $class->cashBooks()
            ->where('type', 'in')
            ->whereNotNull('student_id')
            ->whereBetween('transaction_date', [
                $periode['awal']->copy()->startOfDay(),
                $periode['akhir']->copy()->endOfDay(),
            ])
            ->get(['student_id', 'amount', 'transaction_date']);

        $perSiswa = [];
        foreach ($setoran as $t) {
            $perSiswa[$t->student_id]['jumlah'] = ($perSiswa[$t->student_id]['jumlah'] ?? 0) + (int) $t->amount;
            $perSiswa[$t->student_id]['kali'] = ($perSiswa[$t->student_id]['kali'] ?? 0) + 1;

            $terakhir = $perSiswa[$t->student_id]['terakhir'] ?? null;
            if ($terakhir === null || $t->transaction_date->gt($terakhir)) {
                $perSiswa[$t->student_id]['terakhir'] = $t->transaction_date;
            }
        }

        $students = $class->students()->where('is_active', true)->orderBy('name')->get();

        $baris = $students->map(fn ($s) => [
            'siswa' => $s,
            'jumlah' => $perSiswa[$s->id]['jumlah'] ?? 0,
            'kali' => $perSiswa[$s->id]['kali'] ?? 0,
            'terakhir' => $perSiswa[$s->id]['terakhir'] ?? null,
        ]);

        /*
         * Setoran tanpa nama siswa tetap dilaporkan. Kalau tidak, jumlah pada
         * halaman ini tidak akan pernah cocok dengan saldo di buku besar, dan
         * selisihnya terlihat seperti uang hilang.
         */
        $tanpaNama = (int) $class->cashBooks()
            ->where('type', 'in')
            ->whereNull('student_id')
            ->whereBetween('transaction_date', [
                $periode['awal']->copy()->startOfDay(),
                $periode['akhir']->copy()->endOfDay(),
            ])
            ->sum('amount');

        return view('cashbook.per_siswa', [
            'classroom' => $class,
            'periode' => $periode,
            'baris' => $baris,
            'sudah' => $baris->where('jumlah', '>', 0)->count(),
            'belum' => $baris->where('jumlah', 0)->count(),
            'total' => $baris->sum('jumlah'),
            'tanpaNama' => $tanpaNama,
        ]);
    }

    public function store(Request $request, Classroom $class): RedirectResponse
    {
        $data = $request->validate([
            'transaction_date' => ['required', 'date'],
            'type' => ['required', 'in:in,out'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:191'],
            /*
             * Disaring ke siswa kelas ini. Aturan exists polos akan menerima
             * siswa kelas mana pun — termasuk milik sekolah lain — karena
             * scope tenant pada model tidak berlaku pada query mentah.
             */
            'student_id' => [
                'nullable',
                Rule::exists('students', 'id')->where('class_id', $class->id),
            ],
        ]);

        DB::transaction(function () use ($class, $data) {
            /*
             * balance_after TIDAK dihitung dari saldo akhir kelas.
             *
             * Tanggal transaksi bebas diisi, jadi entri baru bisa mendarat di
             * tengah urutan — bukan di ujung. Menambahkan nominalnya ke saldo
             * total akan memberi entri itu saldo milik posisi terakhir, dan
             * membiarkan seluruh entri sesudahnya basi. Menghitung ulang satu
             * rantai jauh lebih murah daripada buku kas yang salah diam-diam.
             */
            $class->cashBooks()->create($data + [
                'user_id' => $class->user_id,
                'balance_after' => 0,
            ]);

            $this->hitungUlangSaldo($class);
        });

        return back()->with('success', 'Transaksi kas dicatat.');
    }

    public function destroy(Classroom $class, CashBook $cashbook): RedirectResponse
    {
        abort_unless($cashbook->class_id === $class->id, 403);

        DB::transaction(function () use ($class, $cashbook) {
            $cashbook->delete();
            $this->hitungUlangSaldo($class);
        });

        return back()->with('success', 'Transaksi dihapus.');
    }

    /**
     * Tulis ulang balance_after seluruh entri kelas menurut urutan kronologis.
     *
     * Urutannya (transaction_date, id) sengaja sama persis dengan yang dipakai
     * ExportController dan ClassReportBuilder::bukuKas, supaya angka di layar,
     * di ekspor, dan di laporan tidak pernah berselisih.
     *
     * Tidak ada klem ke nol di sini: saldo yang minus adalah keadaan nyata dan
     * harus tercatat apa adanya.
     */
    private function hitungUlangSaldo(Classroom $class): void
    {
        /*
         * Barisnya dikunci selama perhitungan.
         *
         * Dua entri yang disimpan hampir bersamaan — wali kelas menekan tombol
         * dua kali, atau membuka buku kas di dua perangkat — masing-masing
         * membaca rantai yang belum memuat entri satunya, lalu menuliskan
         * balance_after versinya sendiri. Saldo total tetap benar karena
         * dijumlah ulang, tetapi kolom saldo berjalan per baris jadi bohong dan
         * tidak ada yang menyadarinya.
         */
        $entri = $class->cashBooks()
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'type', 'amount', 'balance_after']);

        $berjalan = 0;

        foreach ($entri as $e) {
            $berjalan += $e->type === 'in' ? (int) $e->amount : -((int) $e->amount);

            // Hanya tulis yang benar-benar berubah.
            if ((int) $e->balance_after !== $berjalan) {
                $e->updateQuietly(['balance_after' => $berjalan]);
            }
        }
    }

    private function currentBalance(Classroom $class): int
    {
        $in = (int) $class->cashBooks()->where('type', 'in')->sum('amount');
        $out = (int) $class->cashBooks()->where('type', 'out')->sum('amount');

        return $in - $out;
    }
}
