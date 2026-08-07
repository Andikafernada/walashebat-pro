<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Halaman muka.
 *
 * Dijadikan controller, bukan closure di routes/web.php, karena closure tidak
 * bisa diserialkan: satu saja membuat `route:cache` gagal untuk SELURUH rute
 * aplikasi.
 */
class LandingController extends Controller
{
    /** Harga PRO per bulan, dalam rupiah. */
    public const HARGA_PRO = 10000;

    /**
     * Tanya-jawab halaman muka.
     *
     * Ditaruh di sini, bukan ditulis dua kali di Blade, karena isinya dipakai
     * DUA KALI: sebagai teks yang dibaca pengunjung, dan sebagai JSON-LD
     * FAQPage yang dibaca Google. Google menurunkan peringkat rich result bila
     * keduanya berbeda, dan perbedaan itu justru yang paling mudah terjadi —
     * satu jawaban disunting, salinannya di JSON-LD terlupakan. Dengan satu
     * sumber, keduanya mustahil berselisih.
     *
     * @return list<array{q: string, a: string}>
     */
    public static function faq(): array
    {
        $harga = 'Rp '.number_format(self::HARGA_PRO, 0, ',', '.');
        $bulan = User::BULAN_MASA_GRATIS;

        return [
            [
                'q' => 'Apakah Wali Kelas Hebat gratis?',
                'a' => "Ya. Setiap akun baru mendapat masa gratis {$bulan} bulan penuh dengan seluruh fitur terbuka, tanpa kartu kredit dan tanpa penagihan otomatis. Setelah masa itu habis, aplikasi TETAP bisa dipakai — hanya pengiriman WhatsApp otomatis yang berhenti.",
            ],
            [
                'q' => 'Apa yang terjadi pada data saya kalau masa gratis habis?',
                'a' => 'Tidak ada yang hilang dan tidak ada yang terkunci. Absensi, biodata siswa, buku kas, dan seluruh laporan tetap bisa dibuka, diubah, dan dicetak. Data absensi adalah dokumen wajib sekolah, jadi kami tidak menyanderanya untuk menagih pembayaran.',
            ],
            [
                'q' => 'Berapa biaya setelah masa gratis?',
                'a' => "{$harga} per bulan untuk membuka kembali otomasi WhatsApp. Pembayaran lewat transfer DANA, lalu bukti transfer diunggah dan diverifikasi manual oleh operator — biasanya pada hari yang sama.",
            ],
            [
                'q' => 'Apakah siswa perlu mengunduh aplikasi?',
                'a' => 'Tidak. Siswa dan orang tua cukup membuka tautan yang dibagikan di grup WhatsApp lewat peramban HP. Pengisian biodata dan refleksi harian berjalan tanpa memasang apa pun dan tanpa membuat akun.',
            ],
            [
                'q' => 'Bagaimana cara presensi lewat WhatsApp bekerja?',
                'a' => 'Setiap hari sistem menerbitkan satu tautan absensi sekali pakai beserta PIN 6 digit, lalu mengirimkannya ke Seksi Absensi atau grup kelas. Petugas membuka tautan itu, mencentang kehadiran satu layar, dan rekapnya langsung masuk ke dasbor wali kelas.',
            ],
            [
                'q' => 'Apakah data siswa saya aman dari guru lain?',
                'a' => 'Ya. Setiap wali kelas hanya dapat melihat kelas miliknya sendiri; pemisahan ini ditegakkan di lapisan basis data, bukan sekadar disembunyikan di tampilan. Tautan formulir publik memakai token acak, bukan nomor urut, sehingga daftar siswa tidak bisa ditebak dari alamatnya.',
            ],
            [
                'q' => 'Apakah cocok untuk SD, SMP, SMA, dan SMK?',
                'a' => 'Cocok untuk keempatnya. Jumlah siswa, nama kelas, jenis pelanggaran, bobot poin disiplin, dan dimensi karakter semuanya dapat disesuaikan sendiri oleh wali kelas tanpa bantuan teknis.',
            ],
            [
                'q' => 'Bisakah laporannya dicetak untuk kepala sekolah?',
                'a' => 'Bisa. Rekap absensi bulanan, leger kehadiran, portofolio karakter siswa, dan laporan wali kelas tersedia dalam PDF siap tanda tangan, serta Excel bila datanya masih ingin diolah lagi.',
            ],
        ];
    }

    public function __invoke(): View|RedirectResponse
    {
        return Auth::check()
            ? redirect()->route('dashboard')
            : view('landing', [
                'faq' => self::faq(),
                'hargaPro' => self::HARGA_PRO,
                'bulanGratis' => User::BULAN_MASA_GRATIS,
            ]);
    }
}
