<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi OTP - WaliKelas Pro Student</title>
    <link rel="stylesheet" href="{{ mix('build/assets/app.css') }}">
    <script src="{{ mix('build/assets/app.js') }}" defer></script>
</head>
<body class="h-full bg-slate-50 text-slate-700 antialiased">
    <div class="flex min-h-screen items-center justify-center bg-slate-900 p-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <a href="{{ route('student.login') }}" class="inline-flex items-center gap-2 text-white">
                    <span class="text-lg font-semibold">WaliKelas Pro</span>
                </a>
            </div>

            <div class="card p-8">
                <div class="text-center mb-6">
                    <div class="stat-icon stat-icon--indigo w-16 h-16 mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-900 mb-2">Verifikasi OTP</h2>
                    <p class="text-slate-500">
                        Masukkan kode OTP yang dikirim ke WhatsApp orang tua Anda.<br>
                        <strong class="text-indigo-600">NIS: {{ e($nis) }}</strong>
                    </p>
                </div>

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

                <form action="{{ route('student.otp.submit', ['nis' => e($nis)]) }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="otp" class="form-label">Kode OTP</label>
                        <input type="text" id="otp" name="otp" required autofocus
                               maxlength="6" pattern="[0-9]{6}"
                               placeholder="6 digit kode"
                               class="form-input text-center text-2xl tracking-widest">
                    </div>

                    <div>
                        <label for="password" class="form-label">Password Baru</label>
                        <input type="password" id="password" name="password" required
                               class="form-input">
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               class="form-input">
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center">
                        Reset Password
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('student.password.request') }}" class="text-sm text-indigo-600 hover:text-indigo-700">
                        &larr; Kirim ulang kode OTP
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
