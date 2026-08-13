@extends('layouts.guest')
@section('title', 'Daftar Akun Baru')
@section('content')

    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Buat akun gratis</h2>
        <p class="mt-1 text-sm text-slate-500">Bergabung dengan 500+ guru Indonesia</p>
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
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                required
                autocomplete="name"
                placeholder="Drs. Budi Santoso"
                class="form-input @error('name') form-input--error @enderror"
            >
            @error('name')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email" class="form-label form-label--required">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                placeholder="budi@sekolah.sch.id"
                class="form-input @error('email') form-input--error @enderror"
            >
            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
            <p class="form-hint">Gunakan email resmi sekolah (.sch.id, .edu.id, .or.id)</p>
        </div>

        <!-- WhatsApp -->
        <div class="form-group">
            <label for="whatsapp_number" class="form-label form-label--required">No. WhatsApp</label>
            <input
                id="whatsapp_number"
                name="whatsapp_number"
                type="tel"
                value="{{ old('whatsapp_number') }}"
                required
                autocomplete="tel"
                placeholder="8123456789"
                pattern="[0-9]{9,13}"
                class="form-input @error('whatsapp_number') form-input--error @enderror"
            >
            @error('whatsapp_number')
                <p class="form-error">{{ $message }}</p>
            @enderror
            <p class="form-hint">Kami akan kirim kode verifikasi 6 digit ke WhatsApp ini</p>
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password" class="form-label form-label--required">Kata sandi</label>
            <div class="relative">
                <input
                    id="password"
                    name="password"
                    :type="showPassword ? 'text' : 'password'"
                    @input="checkPasswordStrength()"
                    required
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                    minlength="8"
                    class="form-input pr-11 @error('password') form-input--error @enderror"
                >
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                    <svg x-show="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242"/>
                    </svg>
                </button>
            </div>

            <!-- Password strength indicator -->
            <div class="mt-2 flex gap-1">
                <div class="h-1 flex-1 rounded-sm" :class="passwordStrength >= 1 ? 'bg-rose-500' : 'bg-slate-200'"></div>
                <div class="h-1 flex-1 rounded-sm" :class="passwordStrength >= 2 ? 'bg-amber-500' : 'bg-slate-200'"></div>
                <div class="h-1 flex-1 rounded-sm" :class="passwordStrength >= 3 ? 'bg-emerald-500' : 'bg-slate-200'"></div>
                <div class="h-1 flex-1 rounded-sm" :class="passwordStrength >= 4 ? 'bg-emerald-600' : 'bg-slate-200'"></div>
            </div>
            <p class="text-xs text-slate-500 mt-1" x-show="passwordStrength > 0" x-text="['Lemah', 'Sedang', 'Kuat', 'Sangat Kuat'][passwordStrength - 1]"></p>

            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
            <label for="password_confirmation" class="form-label form-label--required">Konfirmasi kata sandi</label>
            <div class="relative">
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi"
                    class="form-input pr-11"
                >
                <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                    <svg x-show="!showConfirmPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showConfirmPassword" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Terms -->
        <p class="text-xs text-slate-500 text-center">
            Dengan mendaftar, Anda menyetujui
            <a href="#" class="text-indigo-600 hover:underline">Syarat &amp; Ketentuan</a> dan
            <a href="#" class="text-indigo-600 hover:underline">Kebijakan Privasi</a> kami.
        </p>

        <!-- Submit -->
        <button type="submit" class="btn-primary w-full justify-center">
            Daftar &amp; Verifikasi WhatsApp
        </button>
    </form>

    <!-- Login link -->
    <p class="mt-6 text-center text-sm text-slate-500">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:underline">
            Masuk
        </a>
    </p>
@endsection
