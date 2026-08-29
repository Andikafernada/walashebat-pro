@extends('layouts.guest')

@section('title', 'Verifikasi Email Guru - WaliKelas Pro')

@section('content')
<div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-emerald-50 via-slate-50 to-teal-50">
    <div class="w-full max-w-md bg-white rounded-3xl border border-emerald-200 shadow-xl shadow-emerald-600/5 p-6 sm:p-8 space-y-6">

        {{-- Header Logo & Title --}}
        <div class="text-center space-y-2">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-100 border border-emerald-200 text-emerald-700 flex items-center justify-center text-2xl shadow-xs">
                📩
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Verifikasi Email Anda</h1>
            <p class="text-xs sm:text-sm text-slate-600 font-medium">
                Kami telah mengirimkan 6 digit kode OTP ke email:
                <br>
                <span class="font-bold text-emerald-800 font-mono text-sm bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200 inline-block mt-1">
                    {{ $maskedEmail }}
                </span>
            </p>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-semibold flex items-center gap-2">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('warning'))
            <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-semibold flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs font-semibold flex items-center gap-2">
                <span>❌</span>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- OTP Input Form --}}
        <form method="POST" action="{{ route('register.otp.verify', $token) }}" class="space-y-5">
            @csrf

            <div>
                <label for="otp" class="block text-center text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Masukkan 6 Digit Kode OTP
                </label>
                <input
                    id="otp"
                    type="text"
                    name="otp"
                    maxlength="6"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autofocus
                    required
                    placeholder="------"
                    autocomplete="one-time-code"
                    class="block w-full text-center font-mono text-3xl font-extrabold tracking-[12px] sm:tracking-[16px] py-3.5 px-4 rounded-2xl border-2 border-emerald-300 bg-emerald-50/30 text-emerald-950 placeholder-slate-300 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/20 transition-all shadow-inner"
                >
                <p class="text-center text-[11px] text-slate-400 mt-2">
                    Kode berlaku selama 10 menit. Periksa juga folder <em>Spam / Promosi</em> jika belum masuk.
                </p>
            </div>

            <button type="submit"
                    class="w-full py-3.5 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-sm shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-2">
                <span>Verifikasi &amp; Masuk ke Dashboard</span>
                <span>&rarr;</span>
            </button>
        </form>

        {{-- Resend & Change Email Actions --}}
        <div class="pt-4 border-t border-emerald-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <form method="POST" action="{{ route('register.otp.resend', $token) }}" id="resendForm">
                @csrf
                <button type="submit" id="resendBtn" class="font-bold text-emerald-700 hover:text-emerald-900 transition-colors flex items-center gap-1 disabled:opacity-50">
                    <span>🔄</span>
                    <span id="resendText">Kirim Ulang Kode OTP</span>
                </button>
            </form>

            <a href="{{ route('register') }}" class="font-bold text-slate-500 hover:text-rose-600 transition-colors">
                Salah ketik email? Daftar ulang
            </a>
        </div>

    </div>
</div>

<script>
    // 60-second cooldown timer for resend button
    document.addEventListener('DOMContentLoaded', () => {
        const resendBtn = document.getElementById('resendBtn');
        const resendText = document.getElementById('resendText');
        const otpInput = document.getElementById('otp');

        // Only allow numbers
        otpInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            if (e.target.value.length === 6) {
                // Auto-submit when 6 digits are reached
                e.target.form.submit();
            }
        });

        let cooldown = 60;
        resendBtn.disabled = true;

        const timer = setInterval(() => {
            cooldown--;
            if (cooldown > 0) {
                resendText.textContent = `Kirim Ulang (${cooldown}s)`;
            } else {
                clearInterval(timer);
                resendBtn.disabled = false;
                resendText.textContent = 'Kirim Ulang Kode OTP';
            }
        }, 1000);
    });
</script>
@endsection
