<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password - WaliKelas Pro Student</title>
    <link rel="stylesheet" href="{{ mix('build/assets/app.css') }}">
    <script src="{{ mix('build/assets/app.js') }}" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body class="h-full bg-slate-50 text-slate-700 antialiased">
    <div class="flex min-h-screen items-center justify-center bg-slate-900 p-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <a href="{{ route('student.login') }}" class="inline-flex items-center gap-2 text-white">
                    <div class="w-10 h-10 rounded bg-white/10 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13.668m7.088-7.218c2.103-2.048 5.802-3.206 9.912-3.206H21v21m-7.088-7.218c-2.103 2.048-5.802 3.206-9.912 3.206H3m18-17.574V21M12 6.253V3"/>
                        </svg>
                    </div>
                    <span class="text-lg font-semibold">WaliKelas Pro</span>
                </a>
            </div>

            <div class="card p-8">
                <h2 class="text-2xl font-semibold text-slate-900 mb-2">Lupa Password?</h2>
                <p class="text-slate-500 mb-6">
                    Masukkan NIS Anda. Kami akan mengirim kode OTP ke WhatsApp orang tua Anda.
                </p>

                @if (session('status'))
                    <div class="alert alert--success mb-4">
                        <p class="alert__body">{{ session('status') }}</p>
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

                <form action="{{ route('student.password.email') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="nis" class="form-label">NIS</label>
                        <input type="text" id="nis" name="nis" required autofocus
                               value="{{ old('nis') }}"
                               class="form-input">
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center">
                        Kirim Kode OTP
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('student.login') }}" class="text-sm text-indigo-600 hover:text-indigo-700">
                        &larr; Kembali ke halaman login
                    </a>
                </div>
            </div>

            <p class="mt-6 text-center text-sm text-white/60">
                <a href="{{ route('login') }}" class="underline">Login sebagai Wali Kelas</a>
            </p>
        </div>
    </div>
</body>
</html>
