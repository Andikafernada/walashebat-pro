@extends('layouts.guest')

@section('title', 'Verifikasi Nomor WhatsApp')
@section('page-title', 'Masukkan kode verifikasi')
@section('page-subtitle', 'Kode enam digit dikirim lewat WhatsApp ke nomor Anda')

@section('content')
    <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-950 font-medium">
        Kode dikirim ke: <strong class="font-mono text-slate-900 font-bold">{{ $nomorSamar }}</strong>. Buka WhatsApp Anda, lalu ketik kodenya di bawah ini.
    </div>

    @if (session('success'))
        <div class="alert mb-4">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert mb-4">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.verifikasi') }}" class="space-y-4">
        @csrf

        <div class="form-group">
            <label for="otp" class="form-label form-label--required">Kode verifikasi WhatsApp</label>
            <input type="text" id="otp" name="otp" required autofocus
                   inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" pattern="[0-9]{6}"
                   placeholder="123456"
                   class="form-input text-center text-xl font-bold tracking-[0.3em]"
                   style="letter-spacing: 0.3em;">
        </div>

        <button type="submit" class="btn-primary">Verifikasi &amp; Buat Akun</button>
    </form>

    <form method="POST" action="{{ route('register.kirim-ulang') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-xs font-bold text-emerald-800 hover:underline bg-transparent border-0 cursor-pointer">
            Kirim ulang kode OTP
        </button>
    </form>

    <p class="mt-6 text-center text-xs sm:text-sm text-slate-600">
        Salah mengetik nomor? <a href="{{ route('register') }}" class="font-bold text-emerald-800 hover:underline">Ulangi pendaftaran</a>
    </p>
@endsection

@section('footer')
<a href="/" class="text-emerald-800 font-bold hover:underline">← Kembali ke Beranda</a>
@endsection
