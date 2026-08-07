<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Student') - WaliKelas Pro</title>
    <link rel="stylesheet" href="{{ mix('build/assets/app.css') }}">
    <script src="{{ mix('build/assets/app.js') }}" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="h-full font-sans text-slate-800 antialiased">
    <div class="h-full flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 flex flex-col">
            <!-- Logo -->
            <div class="p-4 border-b border-slate-200">
                <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13.668m7.088-7.218c2.103-2.048 5.802-3.206 9.912-3.206H21v21"/>
                        </svg>
                    </div>
                    <span class="font-bold text-slate-900">Student</span>
                </a>
            </div>

            <!-- Nav -->
            <nav class="flex-1 p-4 space-y-1">
                <a href="{{ route('student.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('student.dashboard') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 002 2h14a1 1 0 001-2v-9a1 1 0 00-2H6a2 2 0 00-2v-3a2 2 0 012-2z"/>
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('student.portfolio') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('student.portfolio*') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Portofolio
                </a>

                <a href="{{ route('student.biodata') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('student.biodata*') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0a4 4 0 018 0zM12 14a9 9 0 100-18 9 9 0 0018 0zm9-4.5v4.5l3 3l-3 3m-4.5-10.5h-4.5"/>
                    </svg>
                    Biodata
                </a>
            </nav>

            <!-- User -->
            <div class="p-4 border-t border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="avatar avatar--sm">
                        <span class="avatar-initials bg-indigo-100 text-indigo-600">{{ substr(Auth::guard('student')->user()->name, 0, 1) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ Auth::guard('student')->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ Auth::guard('student')->user()->nis }}</p>
                    </div>
                    <form method="POST" action="{{ route('student.logout') }}">
                        @csrf
                        <button type="submit" class="p-1 text-slate-400 hover:text-red-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l4 4m-4-4l-4 4m4-4l4 4"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            @yield('content')
        </main>
    </div>

    {{-- Tanpa blok ini kegagalan validasi di portal siswa tidak terlihat sama
         sekali: halaman hanya termuat ulang seolah tidak terjadi apa-apa. --}}
    @if($errors->any())
        <div class="fixed bottom-4 right-4 max-w-sm bg-rose-500 text-white px-4 py-3 rounded-lg shadow-lg flex items-start gap-2">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <ul class="space-y-0.5 text-sm">
                @foreach($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="fixed bottom-4 right-4 bg-emerald-500 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif
</body>
</html>
