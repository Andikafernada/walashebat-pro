<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4d7c0f">
    <title>@yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100">
    <div class="min-h-screen flex flex-col">

        {{-- Header --}}
        <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-red-600 flex items-center justify-center text-white font-bold text-sm">🎓</div>
                <div>
                    <span class="font-extrabold text-slate-900 text-sm">WaliKelas Pro</span>
                    <span class="block text-[10px] font-bold text-red-700 uppercase tracking-wider">Pusat Administrasi</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-red-600 hover:text-red-700 font-semibold">Keluar</button>
                </form>
            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1 max-w-5xl mx-auto w-full px-6 py-6">
            @yield('content')
        </main>

    </div>
</body>
</html>
