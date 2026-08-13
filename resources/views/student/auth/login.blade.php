<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Siswa - WaliKelas Pro</title>
    <link rel="stylesheet" href="{{ mix('build/assets/app.css') }}">
    <script src="{{ mix('build/assets/app.js') }}" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body class="h-full bg-slate-50 text-slate-700 antialiased">
    <div class="min-h-screen flex">
        <!-- Left side - Branding -->
        <div class="hidden bg-slate-900 p-12 text-white lg:flex lg:w-1/2 lg:flex-col lg:justify-between xl:w-2/5">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 rounded bg-white/10 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13.668m7.088-7.218c2.103-2.048 5.802-3.206 9.912-3.206H21v21m-7.088-7.218c-2.103 2.048-5.802 3.206-9.912 3.206H3m18-17.574V21M12 6.253V3"/>
                        </svg>
                    </div>
                    <span class="text-xl font-semibold">WaliKelas Pro</span>
                </div>
                <h2 class="text-2xl font-semibold mt-8 mb-4">Portal Siswa</h2>
                <p class="text-white/80 mb-8">
                    Lihat dan perbarui portofolio karakter,catatan refleksi, dan data dirimu sendiri.
                </p>
            </div>

            <div class="space-y-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded bg-white/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6.618 2.443c.457-.443.811-1.008.811-1.66c0-.65-.35-1.218-.811-1.66m-3.618-.443V5.618m3-.443V3"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium">Portofolio Karakter</p>
                        <p class="text-sm text-white/60">Catat pencapaian dan refleksi</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded bg-white/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0a4 4 0 018 0zM12 14a9 9 0 100-18 9a9 9 0 0018 0zm9-4.5v4.5l3 3l-3 3m-4.5-10.5h-4.5"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium">Biodata Diri</p>
                        <p class="text-sm text-white/60">Perbarui data kontak orang tua</p>
                    </div>
                </div>
            </div>

            <p class="text-sm text-white/40">© {{ date('Y') }} WaliKelas Pro</p>
        </div>

        <!-- Right side - Form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Mobile logo -->
                <div class="lg:hidden text-center mb-8">
                    <div class="inline-flex items-center gap-2 text-slate-900 mb-4">
                        <div class="w-10 h-10 rounded border border-slate-200 bg-white flex items-center justify-center text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13.668m7.088-7.218c2.103-2.048 5.802-3.206 9.912-3.206H21v21m-7.088-7.218c-2.103 2.048-5.802 3.206-9.912 3.206H3m18-17.574V21M12 6.253V3"/>
                            </svg>
                        </div>
                        <span class="text-lg font-semibold">WaliKelas Pro</span>
                    </div>
                    <h1 class="text-2xl font-semibold text-slate-900">Portal Siswa</h1>
                </div>

                <div class="card p-8">
                    <h2 class="text-2xl font-semibold text-slate-900 mb-2">Login Siswa</h2>
                    <p class="text-slate-500 mb-6">Masuk dengan NIS dan password Anda</p>

                    @if (session('error'))
                        <div class="alert alert--warning mb-4">
                            <p class="alert__body">{{ session('error') }}</p>
                        </div>
                    @endif

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

                    <form method="POST" action="{{ route('student.login') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="nis" class="form-label">NIS</label>
                            <input type="text" id="nis" name="nis" value="{{ old('nis') }}" required autofocus
                                   class="form-input">
                        </div>

                        <div>
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" required
                                   class="form-input">
                        </div>

                        <button type="submit" class="btn-primary w-full justify-center">
                            Masuk
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <a href="{{ route('student.password.request') }}" class="text-sm text-indigo-600 hover:text-indigo-700">
                            Lupa password?
                        </a>
                    </div>
                </div>

                <p class="mt-6 text-center text-sm text-slate-500">
                    <a href="{{ route('login') }}" class="underline">Login sebagai Wali Kelas</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
