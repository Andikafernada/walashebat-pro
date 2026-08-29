<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#10b981">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Masuk') - {{ config('app.name', 'WaliKelas Pro') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icon-192.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --safe-area-inset-top: env(safe-area-inset-top, 0px);
            --safe-area-inset-bottom: env(safe-area-inset-bottom, 0px);
        }

        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f0fdf4 !important;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }

        .auth-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding: 16px;
            max-width: 440px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        .auth-header {
            text-align: center;
            padding: 24px 0 16px 0;
            padding-top: max(24px, env(safe-area-inset-top));
        }

        .auth-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .auth-logo-icon {
            width: 44px;
            height: 44px;
            background: #059669;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        }

        .auth-logo-text {
            text-align: left;
        }

        .auth-logo-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        .auth-logo-subtitle {
            font-size: 11px;
            font-weight: 700;
            color: #059669;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .auth-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding-bottom: max(20px, env(safe-area-inset-bottom));
        }

        .auth-card {
            background: #ffffff;
            border: 1.5px solid #a7f3d0;
            border-radius: 24px;
            padding: 28px 24px;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.08);
            width: 100%;
            box-sizing: border-box;
        }

        .auth-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 6px 0;
            letter-spacing: -0.01em;
        }

        .auth-subtitle {
            font-size: 13px;
            color: #334155;
            margin: 0 0 20px 0;
            line-height: 1.4;
        }

        .auth-footer {
            text-align: center;
            padding: 16px 0;
            font-size: 12px;
            color: #475569;
        }

        .auth-footer a {
            color: #059669;
            font-weight: 700;
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .form-label--required::after {
            content: ' *';
            color: #0f172a;
            font-weight: 800;
        }

        .form-input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #a7f3d0;
            border-radius: 12px;
            font-size: 14px;
            color: #0f172a;
            background: #ffffff;
            transition: all 0.2s ease;
            -webkit-appearance: none;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: #059669;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .form-input--error {
            border-color: #0f172a;
            background: #f0fdf4;
        }

        .form-error {
            font-size: 12px;
            color: #0f172a;
            font-weight: 700;
            margin-top: 4px;
        }

        .form-hint {
            font-size: 11px;
            color: #475569;
            margin-top: 4px;
        }

        .form-checkbox {
            width: 18px;
            height: 18px;
            border-radius: 5px;
            border: 1.5px solid #059669;
            accent-color: #059669;
        }

        /* Button Styles */
        .btn-primary {
            width: 100%;
            padding: 13px 20px;
            background: #059669;
            color: #ffffff;
            font-size: 14.5px;
            font-weight: 700;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
            text-decoration: none;
            box-sizing: border-box;
        }

        .btn-primary:hover:not(:disabled) {
            background: #047857;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35);
        }

        .btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            width: 100%;
            padding: 13px 20px;
            background: #ffffff;
            color: #064e3b;
            font-size: 14.5px;
            font-weight: 700;
            border: 1.5px solid #a7f3d0;
            border-radius: 14px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .btn-secondary:hover {
            background: #f0fdf4;
            border-color: #059669;
        }

        /* Alert Styles */
        .alert {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 13px;
            line-height: 1.4;
            background: #f0fdf4;
            color: #064e3b;
            border: 1.5px solid #a7f3d0;
            font-weight: 600;
        }

        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #059669;
            cursor: pointer;
            padding: 4px;
            background: transparent;
            border: none;
        }

        .password-toggle:hover {
            color: #047857;
        }

        /* Link styles */
        .forgot-link {
            font-size: 12px;
            color: #059669;
            text-decoration: none;
            font-weight: 700;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Spinner */
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Password strength */
        .password-strength {
            display: flex;
            gap: 4px;
            margin-top: 8px;
        }

        .password-strength-bar {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: #a7f3d0;
            transition: background 0.2s ease;
        }

        .password-strength-bar.active {
            background: #059669;
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body>

<div class="auth-container">
    {{-- Header with Logo --}}
    <header class="auth-header">
        <a href="/" class="auth-logo">
            <div class="auth-logo-icon">🎓</div>
            <div class="auth-logo-text">
                <div class="auth-logo-title">WaliKelas <span style="color: #059669;">Pro</span></div>
                <div class="auth-logo-subtitle">walas.my.id</div>
            </div>
        </a>
    </header>

    {{-- Main Content --}}
    <main class="auth-content">
        <div class="auth-card">
            {{-- Page Title --}}
            <h1 class="auth-title">@yield('page-title', 'Masuk')</h1>
            <p class="auth-subtitle">@yield('page-subtitle', 'Kelola administrasi kelas Anda')</p>

            {{-- Flash Messages --}}
            @if (session('error'))
                <div class="alert">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="alert">{{ session('success') }}</div>
            @endif

            {{-- Main Form Content --}}
            @yield('content')
        </div>
    </main>

    {{-- Footer --}}
    <footer class="auth-footer">
        @yield('footer')
        <p class="mt-2 text-slate-500 font-medium">&copy; {{ date('Y') }} WaliKelas Pro</p>
    </footer>
</div>

{{-- Additional Scripts --}}
@stack('scripts')
</body>
</html>
