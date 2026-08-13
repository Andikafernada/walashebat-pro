@extends('layouts.guest')

@section('title', 'Verifikasi Nomor WhatsApp')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Masukkan kode verifikasi</h2>
        <p class="mt-1 text-sm text-slate-500">
            Kode enam digit dikirim lewat WhatsApp ke
            <strong class="font-mono text-slate-700">{{ $nomorSamar }}</strong>.
            Buka WhatsApp Anda, lalu ketik kodenya di sini.
        </p>
    </div>

    @if (session('success'))
        <div class="alert alert--success mb-4">
            <div class="alert__body">{{ session('success') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert--danger mb-4">
            <div class="alert__body">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{--
        Satu kolom, bukan enam kotak.

        Versi sebelumnya memecah kode menjadi enam kotak yang saling melempar
        fokus — dan tidak pernah bekerja, karena x-model menunjuk 'otpInputs'
        yang tak pernah didefinisikan. Enam kotak juga memusuhi cara kode ini
        sampai: guru menyalin kodenya dari WhatsApp di ponsel yang sama, dan
        tempel ke enam kotak terpisah hanya mengisi kotak pertama.

        autocomplete="one-time-code" membiarkan papan ketik menawarkan kodenya
        sendiri bila peramban mengenalinya.
    --}}
    <form method="POST" action="{{ route('register.verifikasi') }}" class="space-y-4">
        @csrf

        <div>
            <label for="otp" class="form-label form-label--required">Kode verifikasi</label>
            <input type="text" id="otp" name="otp" required autofocus
                   inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" pattern="[0-9]{6}"
                   placeholder="6 digit"
                   class="form-input text-center text-2xl tracking-[0.4em]">
        </div>

        <button type="submit" class="btn-primary w-full">Verifikasi &amp; buat akun</button>
    </form>

    <form method="POST" action="{{ route('register.kirim-ulang') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="btn-ghost btn-ghost--sm">Kirim ulang kode</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Salah mengetik nomor? <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Ulangi pendaftaran</a>
    </p>
@endsection
