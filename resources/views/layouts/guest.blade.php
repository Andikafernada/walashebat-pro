<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - WaliKelas Pro</title>
    {{-- @vite, bukan mix(). Aplikasi ini dibangun dengan Vite; public/mix-manifest.json
         hanyalah berkas tempelan yang isinya disalin manual dari manifest Vite.
         Selama nama berkasnya kebetulan masih sama, halaman ini tampak baik —
         tetapi build aset berikutnya menghasilkan hash baru yang tidak ikut
         tersalin, dan halaman masuk kehilangan seluruh gayanya tanpa ada yang
         menyadari sampai ada pengguna yang mengeluh. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📚</text></svg>">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        /* Background pattern */
        .auth-bg {
            background-color: #f8fafc;
            background-image:
                radial-gradient(at 20% 20%, rgba(99,102,241,0.08) 0, transparent 50%),
                radial-gradient(at 80% 80%, rgba(124,58,237,0.06) 0, transparent 50%),
                radial-gradient(at 50% 0%, rgba(56,189,248,0.05) 0, transparent 50%);
        }

        .dot-pattern {
            background-image: radial-gradient(circle, #e2e8f0 1px, transparent 1px);
            background-size: 24px 24px;
        }

        /* Logo glow */
        .logo-glow {
            box-shadow: 0 12px 30px -6px rgba(79,70,229,0.35);
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }
        .logo-glow:hover {
            box-shadow: 0 16px 40px -6px rgba(79,70,229,0.45);
            transform: translateY(-2px);
        }

        /* Card entrance */
        .auth-card {
            animation: card-enter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        @keyframes card-enter {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Brand entrance */
        .brand-enter {
            animation: brand-enter 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.1s both;
        }
        @keyframes brand-enter {
            from {
                opacity: 0;
                transform: translateY(12px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>

    {{-- Halaman masuk, daftar, dan verifikasi masing-masing menitipkan gayanya
         lewat @push('styles'). Tanpa baris ini titipan itu dibuang diam-diam. --}}
    @stack('styles')
</head>
<body class="auth-bg flex min-h-full items-start justify-center p-4 pt-12 sm:pt-20">

<!-- Subtle dot pattern overlay -->
<div class="fixed inset-0 dot-pattern pointer-events-none opacity-40" aria-hidden="true"></div>

<div class="relative z-10 w-full max-w-md">

    <!-- Brand Header -->
    <div class="brand-enter mb-8 text-center">
        <a href="/" class="inline-block">
            <div class="logo-glow mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                </svg>
            </div>
        </a>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">WaliKelas <span class="text-indigo-600">Pro</span></h1>
        <p class="mt-2 text-sm text-slate-500">Kelola administrasi kelas dengan mudah &amp; efisien</p>
    </div>

    <!-- Auth Card -->
    <div class="auth-card rounded-3xl border border-slate-200/80 bg-white p-8 shadow-xl" style="box-shadow: 0 25px 60px -12px rgba(0,0,0,0.08), 0 0 0 1px rgba(0,0,0,0.03);">
        @yield('content')
    </div>

    <!-- Footer -->
    <p class="mt-6 text-center text-xs text-slate-400" style="animation: card-enter 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.3s both;">
        &copy; {{ date('Y') }} WaliKelas Pro &middot; Multi-tenant SaaS
    </p>
</div>

</body>
</html>
