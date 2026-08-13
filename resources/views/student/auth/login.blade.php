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
                    <span class="text-xl font-semibold">WaliKelas Pro</span>
                </div>
                <h2 class="text-2xl font-semibold text-white mt-8 mb-4">Portal Siswa</h2>
                <p class="text-white/80 mb-8">
                    Lihat dan perbarui portofolio karakter,catatan refleksi, dan data dirimu sendiri.
                </p>
            </div>

            <div class="space-y-6">
                <div class="flex items-start gap-4">
                    <div>
                        <p class="font-medium">Portofolio Karakter</p>
                        <p class="text-sm text-white/60">Catat pencapaian dan refleksi</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
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
