<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Login Siswa - WaliKelas Pro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background-color: #f0fdf4 !important;
            color: #0f172a;
        }
    </style>
</head>
<body class="min-h-screen bg-[#f0fdf4] text-slate-900 antialiased flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo & Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-emerald-600 text-white text-2xl font-bold shadow-sm mb-3">🎓</div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Portal Siswa</h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-0.5">WaliKelas Pro · walas.my.id</p>
        </div>

        <div class="bg-white border border-emerald-200 rounded-3xl p-6 sm:p-8 shadow-md">
            <h2 class="text-lg font-bold text-slate-900 mb-1">Masuk Siswa</h2>
            <p class="text-xs text-slate-600 mb-5">Gunakan Nomor Induk Siswa (NIS) dan kata sandi Anda</p>

            @if (session('error'))
                <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-950 font-bold mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('status'))
                <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-950 font-bold mb-4">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-slate-900 font-bold mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('student.login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="nis" class="block text-xs font-bold text-slate-900 mb-1.5">NIS (Nomor Induk Siswa)</label>
                    <input type="text" id="nis" name="nis" value="{{ old('nis') }}" required autofocus
                           placeholder="Contoh: 12345"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-emerald-200 bg-white text-slate-900 text-sm font-semibold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-900 mb-1.5">Kata Sandi</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Masukkan kata sandi"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-emerald-200 bg-white text-slate-900 text-sm font-semibold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-xs transition-all">
                    Masuk ke Portal Siswa
                </button>
            </form>

            <div class="mt-5 text-center">
                @if(Route::has('student.password.request'))
                    <a href="{{ route('student.password.request') }}" class="text-xs font-bold text-emerald-800 hover:underline">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-slate-600 font-medium">
            Bukan siswa? <a href="{{ route('login') }}" class="font-bold text-emerald-800 hover:underline">Login sebagai Guru / Wali Kelas</a>
        </p>
    </div>
</body>
</html>
