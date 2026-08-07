<?php

namespace App\Http\Controllers;

use App\Models\PaymentProof;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminSubscriptionController extends Controller
{
    /** Daftar bukti pembayaran pending & riwayat */
    public function index(): View
    {
        $pending = PaymentProof::with('user')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $history = PaymentProof::with(['user', 'approver'])
            ->where('status', '!=', 'pending')
            ->latest()
            ->paginate(20);

        return view('admin.subscriptions.index', compact('pending', 'history'));
    }

    /** Setujui pembayaran & aktifkan status PRO */
    public function approve(Request $request, PaymentProof $proof): RedirectResponse
    {
        if ($proof->status !== 'pending') {
            return back()->with('warning', 'Transaksi ini sudah diproses sebelumnya.');
        }

        $user = $proof->user;

        /*
         * Jumlah bulan ditentukan admin saat menyetujui, bukan dikunci oleh
         * paket yang dipilih guru di formulir. Verifikasinya memang manual:
         * admin melihat nominal transfer DANA yang masuk, dan nominal itu bisa
         * tidak persis sama dengan paket yang diklaim — misalnya guru
         * mentransfer untuk tiga bulan sekaligus lewat formulir "bulanan".
         *
         * Bila tidak diisi, dipakai jumlah bulan sesuai paket yang dipilih guru,
         * sehingga alur persetujuan cepat (satu klik) tetap berjalan seperti
         * sebelumnya.
         */
        $data = $request->validate([
            'bulan' => ['nullable', 'integer', 'min:1', 'max:24'],
        ], [
            'bulan.max' => 'Maksimal 24 bulan sekali persetujuan.',
            'bulan.min' => 'Jumlah bulan minimal 1.',
        ]);

        $months = $data['bulan'] ?? ($proof->plan_type === 'yearly' ? 12 : 1);

        /*
         * Sisa masa yang belum terpakai TIDAK hangus: perpanjangan ditumpuk di
         * atasnya. Guru yang membayar lebih awal tidak dirugikan karena
         * membayar sebelum masanya habis.
         */
        $currentEnd = $user->subscription_ends_at && $user->subscription_ends_at->isFuture()
            ? $user->subscription_ends_at
            : now();

        $newEnd = $currentEnd->copy()->addMonths($months);

        // subscription_tier/ends_at & status pada PaymentProof sengaja di-guard agar
        // tidak bisa diisi lewat mass assignment. Penulisan resmi lewat forceFill().
        $user->forceFill([
            'subscription_tier' => User::TIER_PRO,
            'subscription_ends_at' => $newEnd,
        ])->save();

        $proof->forceFill([
            'status' => 'approved',
            // Jejak audit: berapa bulan yang benar-benar diberikan, yang bisa
            // berbeda dari paket yang diklaim di formulir.
            'granted_months' => $months,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ])->save();

        /*
         * Konfirmasi dikirim lewat NotificationChannel — kontrak yang sama
         * dipakai OTP siswa dan magic link absensi.
         *
         * Sebelumnya memanggil AttendanceSessionService::dispatchMagicLinkRaw(),
         * method yang TIDAK PERNAH ADA. BadMethodCallException-nya ditelan
         * catch(\Throwable) di bawah tanpa satu baris log pun, jadi konfirmasi
         * PRO tidak pernah sampai ke wali kelas mana pun — dan tidak ada yang
         * bisa mengetahuinya. Kegagalan gateway kini dicatat, bukan dibuang.
         */
        $adminWa = auth()->user()->whatsapp_number;

        if ($user->whatsapp_number) {
            $msg = "🎉 *PEMBAYARAN PRO DIKONFIRMASI*\n\n"
                . "Halo Bpk/Ibu *{$user->name}*,\n"
                // Menyebut jumlah bulannya secara eksplisit. Kalau admin
                // memberi bulan berbeda dari paket yang dipilih guru, pesan ini
                // yang menjelaskannya — bukan guru yang menghitung sendiri dari
                // selisih tanggal lalu curiga.
                . "Pembayaran PRO {$months} bulan Anda telah kami terima!\n\n"
                . "✅ Status: *PRO AKTIF*\n"
                . "📅 Berlaku Hingga: *{$newEnd->translatedFormat('d F Y')}*\n\n"
                . "Terima kasih telah berlangganan WaliKelas Pro! 🚀";

            try {
                app(NotificationChannel::class)->send(
                    $user->whatsapp_number,
                    $msg,
                    ['type' => 'subscription_approved', 'payment_proof_id' => $proof->id],
                    $adminWa,
                );
            } catch (\Throwable $e) {
                // Pembayarannya SUDAH disetujui; kegagalan kirim tidak boleh
                // membatalkan itu, tapi juga tidak boleh hilang tanpa jejak.
                Log::error('[langganan] Konfirmasi PRO gagal dikirim', [
                    'user_id' => $user->id,
                    'payment_proof_id' => $proof->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', "Pembayaran {$user->name} disetujui! Akses PRO aktif hingga {$newEnd->translatedFormat('d F Y')}.");
    }

    /** Tolak pembayaran */
    public function reject(Request $request, PaymentProof $proof): RedirectResponse
    {
        // Penjagaan yang sama dengan approve(). Tanpa ini pembayaran yang sudah
        // disetujui masih bisa ditandai ditolak, meninggalkan wali kelas berstatus
        // PRO dengan catatan pembayaran berstatus "rejected".
        if ($proof->status !== 'pending') {
            return back()->with('warning', 'Transaksi ini sudah diproses sebelumnya.');
        }

        $request->validate([
            // Kolomnya varchar(191); max:255 membuat alasan panjang lolos
            // validasi lalu ditolak database sebagai galat 500.
            'reason' => ['nullable', 'string', 'max:191'],
        ]);

        $proof->forceFill([
            'status' => 'rejected',
            'rejection_reason' => $request->reason ?: 'Bukti transfer tidak terbaca / nominal tidak sesuai.',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ])->save();

        return back()->with('info', 'Bukti pembayaran ditolak.');
    }
}
