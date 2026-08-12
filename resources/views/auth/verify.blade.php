@extends('layouts.guest')
@section('title', 'Verifikasi WhatsApp')
@push('styles')
<style>
/* OTP input styles */
.otp-input {
    transition: all 0.2s ease;
}
.otp-input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    transform: scale(1.02);
}
</style>
@endpush
@section('content')

    <!-- Header -->
    <div class="text-center mb-8">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-indigo-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.216 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-semibold text-slate-900">Verifikasi WhatsApp</h2>
        <p class="mt-2 text-sm text-slate-500">Masukkan kode OTP yang telah dikirim ke WhatsApp Anda</p>
    </div>

    <!-- Status Messages -->
    @if(session('status'))
        <div class="mb-6 p-4 rounded bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('register.verify-otp') }}" class="space-y-6" x-data="{
        loading: false,
        otp: '',
        timer: 60,
        init() {
            setInterval(() => {
                if (this.timer > 0) this.timer--;
            }, 1000);

            // Focus first input
            document.querySelector('.otp-input')?.focus();
        }
    }">
        @csrf

        <!-- OTP Input -->
        <div class="form-group">
            <label for="otp" class="form-label form-label--required text-center block">Kode OTP</label>
            <div class="flex gap-2 justify-center mt-3">
                @for($i = 0; $i < 6; $i++)
                    <input
                        type="text"
                        maxlength="1"
                        inputmode="numeric"
                        pattern="[0-9]"
                        x-model="otpInputs[{{ $i }}]"
                        @input="
                            if($event.target.value.length === 1 && {{ $i }} < 5) {
                                $nextTick(() => document.querySelectorAll('.otp-input')[{{ $i + 1 }}]?.focus());
                            }
                            otp = Object.values(otpInputs).join('');
                        "
                        @keydown.backspace="
                            if(!otpInputs[{{ $i }}] && {{ $i }} > 0) {
                                $nextTick(() => document.querySelectorAll('.otp-input')[{{ $i - 1 }}]?.focus());
                            }
                        "
                        class="otp-input w-12 h-14 text-center text-xl font-semibold rounded border-2 border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 @error('otp') border-red-300 @enderror"
                        required
                    >
                @endfor
            </div>
            <input type="hidden" name="otp" x-model="otp">
            @error('otp')
                <p class="form-error mt-3 text-center">{{ $message }}</p>
            @enderror
        </div>

        <!-- Timer -->
        <div class="text-center">
            <p class="text-sm text-slate-500">
                <span x-show="timer > 0">
                    Kirim ulang dalam <strong x-text="timer"></strong> detik
                </span>
                <span x-show="timer === 0">
                    Tidak menerima kode?
                    <a href="{{ route('register.resend-otp') }}"
                       class="text-indigo-600 font-medium hover:text-indigo-700">
                        Kirim ulang
                    </a>
                </span>
            </p>
        </div>

        <!-- Submit -->
        <button
            type="submit"
            class="btn-primary w-full justify-center py-4 text-base font-semibold"
            :disabled="loading || otp.length < 6"
            :class="(loading || otp.length < 6) ? 'opacity-70 cursor-not-allowed' : ''"
        >
            <span x-show="!loading" class="inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Verifikasi
            </span>
            <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                <span class="spinner spinner--white"></span>
                Memverifikasi...
            </span>
        </button>
    </form>

    <!-- Back button -->
    <div class="mt-6 text-center">
        <a href="{{ route('register') }}"
           class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke formulir daftar
        </a>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('otpInputs', () => ({!! json_encode(array_fill(0, 6, '')) !!}));
        });
    </script>
@endsection
