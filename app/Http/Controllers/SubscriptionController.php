<?php

namespace App\Http\Controllers;

use App\Models\PaymentProof;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionController extends Controller
{
    /** Tampilkan halaman langganan PRO & QRIS payment */
    public function index(): View
    {
        $user = auth()->user();
        $pendingProof = PaymentProof::where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        $history = PaymentProof::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('subscription.index', compact('user', 'pendingProof', 'history'));
    }

    /** Unggah bukti transfer / QRIS */
    public function storeProof(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_type' => ['required', 'in:monthly,yearly'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'sender_name' => ['required', 'string', 'max:150'],
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = auth()->user();

        // Check if pending proof exists
        $existing = PaymentProof::where('user_id', $user->id)->where('status', 'pending')->exists();
        if ($existing) {
            return back()->with('warning', 'Anda masih memiliki konfirmasi pembayaran yang sedang diproses admin.');
        }

        /*
         * Disimpan di disk 'local' (storage/app/private), BUKAN 'public'.
         *
         * Bukti pembayaran adalah tangkapan layar DANA/transfer: berisi nama
         * pemilik rekening, nominal, dan sering pula sebagian nomor rekening.
         * Disk 'public' tertaut ke public/storage sehingga berkasnya terlayani
         * langsung oleh nginx kepada siapa saja yang memegang alamatnya — tanpa
         * login, tanpa jejak siapa yang membukanya. Nama berkas acak hanya
         * menyembunyikan, bukan melindungi: alamat itu bocor lewat riwayat
         * peramban, tangkapan layar, maupun teruskan pesan.
         *
         * Di disk privat, satu-satunya jalan masuk adalah showProof() di bawah
         * yang memeriksa pemilik atau admin lebih dulu.
         */
        $path = $request->file('proof_image')->store('payment_proofs', 'local');
        $amount = $request->plan_type === 'yearly' ? 149000 : 19000;

        PaymentProof::create([
            'user_id' => $user->id,
            'plan_type' => $request->plan_type,
            'amount' => $amount,
            'proof_image' => $path,
            'bank_name' => $request->bank_name,
            'sender_name' => $request->sender_name,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Bukti pembayaran berhasil diunggah! Admin akan memverifikasi akun PRO Anda segera.');
    }

    /**
     * Tampilkan berkas bukti pembayaran.
     *
     * Satu-satunya jalan membaca berkas di disk privat. Yang boleh melihat
     * hanya dua pihak: wali kelas yang mengunggahnya, dan admin yang bertugas
     * memverifikasi. Wali kelas lain tidak — bukti pembayaran memuat nama dan
     * nominal yang bukan urusan mereka.
     */
    public function showProof(PaymentProof $proof): StreamedResponse
    {
        $user = auth()->user();

        abort_unless($proof->user_id === $user->id || $user->isAdmin(), 403);

        abort_unless(Storage::disk('local')->exists($proof->proof_image), 404);

        /*
         * inline + CSP sandbox: berkasnya diunggah pengguna. Header ini
         * mencegah SVG atau HTML yang menyamar sebagai gambar dijalankan
         * sebagai halaman di domain kita sendiri.
         */
        return Storage::disk('local')->response($proof->proof_image, null, [
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
