@extends('layouts.guest')
@section('title', 'Daftar Akun Baru')
@push('styles')
<style>
/* Input animations */
.input-animate {
    transition: all 0.2s ease;
}
.input-animate:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

/* Button effects */
.btn-register {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}
.btn-register::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}
.btn-register:hover::before {
    left: 100%;
}
.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px -5px rgba(79, 70, 229, 0.4);
}

/* Phone input with country flag */
.phone-input-wrapper {
    position: relative;
}
.phone-input-wrapper::before {
    content: '🇮🇩';
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.25rem;
    pointer-events: none;
}
.phone-input-wrapper input {
    padding-left: 48px !important;
}

/* Password strength indicator */
.password-strength {
    height: 4px;
    border-radius: 2px;
    transition: all 0.3s ease;
}
</style>
@endpush
@section('content')

    <!-- Header -->
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Buat akun gratis</h2>
        <p class="mt-2 text-sm text-slate-500">Bergabung dengan 500+ guru Indonesia</p>
    </div>

    <!-- Decorative badges -->
    <div class="flex justify-center gap-2 mb-8">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-600 border border-emerald-100">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            Gratis Selamanya
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600 border border-indigo-100">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
            </svg>
            Verifikasi WA
        </span>
    </div>

    <!-- Benefits -->
    <div class="grid grid-cols-3 gap-2 mb-8">
        <div class="p-3 rounded-xl bg-slate-50 text-center">
            <svg class="w-6 h-6 mx-auto mb-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs text-slate-600 font-medium">Absensi WA</p>
        </div>
        <div class="p-3 rounded-xl bg-slate-50 text-center">
            <svg class="w-6 h-6 mx-auto mb-1 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-xs text-slate-600 font-medium">Laporan</p>
        </div>
        <div class="p-3 rounded-xl bg-slate-50 text-center">
            <svg class="w-6 h-6 mx-auto mb-1 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-xs text-slate-600 font-medium">Buku Kas</p>
        </div>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="space-y-5" x-data="{
        loading: false,
        showPassword: false,
        showConfirmPassword: false,
        passwordStrength: 0,

        checkPasswordStrength() {
            const password = document.getElementById('password')?.value || '';
            let strength = 0;

            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            this.passwordStrength = strength;
        }
    }">
        @csrf

        <!-- Nama -->
        <div class="form-group">
            <label for="name" class="form-label form-label--required">Nama lengkap</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    autocomplete="name"
                    placeholder="Drs. Budi Santoso"
                    class="input-animate pl-12 pr-4 py-3.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white @error('name') border-red-300 focus:border-red-500 @enderror"
                >
            </div>
            @error('name')
                <p class="form-error mt-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email" class="form-label form-label--required">Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    placeholder="budi@sekolah.sch.id"
                    class="input-animate pl-12 pr-4 py-3.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white @error('email') border-red-300 @enderror"
                >
            </div>
            @error('email')
                <p class="form-error mt-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
            <p class="form-hint mt-1.5 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                Gunakan email resmi sekolah (.sch.id, .edu.id, .or.id)
            </p>
        </div>

        <!-- WhatsApp -->
        <div class="form-group">
            <label for="whatsapp_number" class="form-label form-label--required">No. WhatsApp</label>
            <div class="relative phone-input-wrapper">
                <input
                    id="whatsapp_number"
                    name="whatsapp_number"
                    type="tel"
                    value="{{ old('whatsapp_number') }}"
                    required
                    autocomplete="tel"
                    placeholder="8123456789"
                    pattern="[0-9]{9,13}"
                    class="input-animate pl-12 pr-4 py-3.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white @error('whatsapp_number') border-red-300 @enderror"
                >
            </div>
            @error('whatsapp_number')
                <p class="form-error mt-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
            <p class="form-hint mt-1.5 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Kami akan kirim kode verifikasi 6 digit ke WhatsApp ini
            </p>
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password" class="form-label form-label--required">Kata sandi</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <input
                    id="password"
                    name="password"
                    :type="showPassword ? 'text' : 'password'"
                    @input="checkPasswordStrength()"
                    required
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                    minlength="8"
                    class="input-animate pl-12 pr-12 py-3.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white @error('password') border-red-300 @enderror"
                >
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                    <svg x-show="!showPassword" class="w-5 h-5 text-slate-400 hover:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="w-5 h-5 text-slate-400 hover:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242"/>
                    </svg>
                </button>
            </div>

            <!-- Password strength indicator -->
            <div class="mt-2">
                <div class="flex gap-1">
                    <div class="flex-1 h-1 rounded-full transition-colors" :class="passwordStrength >= 1 ? 'bg-red-500' : 'bg-slate-200'"></div>
                    <div class="flex-1 h-1 rounded-full transition-colors" :class="passwordStrength >= 2 ? 'bg-amber-500' : 'bg-slate-200'"></div>
                    <div class="flex-1 h-1 rounded-full transition-colors" :class="passwordStrength >= 3 ? 'bg-emerald-500' : 'bg-slate-200'"></div>
                    <div class="flex-1 h-1 rounded-full transition-colors" :class="passwordStrength >= 4 ? 'bg-emerald-600' : 'bg-slate-200'"></div>
                </div>
                <p class="text-xs text-slate-500 mt-1" x-show="passwordStrength > 0" x-text="['Lemah', 'Sedang', 'Kuat', 'Sangat Kuat'][passwordStrength - 1]"></p>
            </div>

            @error('password')
                <p class="form-error mt-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
            <label for="password_confirmation" class="form-label form-label--required">Konfirmasi kata sandi</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi"
                    class="input-animate pl-12 pr-12 py-3.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white"
                >
                <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                    <svg x-show="!showConfirmPassword" class="w-5 h-5 text-slate-400 hover:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showConfirmPassword" x-cloak class="w-5 h-5 text-slate-400 hover:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Terms -->
        <p class="text-xs text-slate-500 text-center">
            Dengan mendaftar, Anda menyetujui
            <a href="#" class="text-indigo-600 hover:underline">Syarat & Ketentuan</a> dan
            <a href="#" class="text-indigo-600 hover:underline">Kebijakan Privasi</a> kami.
        </p>

        <!-- Submit -->
        <button
            type="submit"
            class="btn-register btn-primary w-full justify-center py-4 text-base font-semibold"
        >
            <span class="inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.216 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                Daftar & Verifikasi WhatsApp
            </span>
        </button>
    </form>

    <!-- Login link -->
    <p class="mt-6 text-center text-sm text-slate-500">
        Sudah punya akun?
        <a href="{{ route('login') }}"
           class="font-semibold text-indigo-600 hover:text-indigo-700 hover:underline transition-colors">
            Masuk &rarr;
        </a>
    </p>
@endsection
