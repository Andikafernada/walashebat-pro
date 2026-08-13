@extends('layouts.student')
@section('title', 'Ubah Password')

@section('content')
<div class="p-6 lg:p-8 max-w-lg mx-auto">
    <div class="card">
        <div class="text-center mb-6">
            <div class="stat-icon stat-icon--amber w-16 h-16 mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-slate-900">Ubah Password</h1>
            <p class="text-slate-500 mt-1">
                @isset($must_change)
                    @if($must_change)
                        Anda harus mengubah password untuk pertama kali.
                    @else
                        Ubah password secara berkala untuk keamanan akun Anda.
                    @endif
                @else
                    Ubah password Anda di sini.
                @endif
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

        <form action="{{ route('student.password.change') }}" method="POST" class="space-y-4">
            @csrf

            @if($must_change)
                <input type="hidden" name="current_password" value="none">
            @else
                <div>
                    <label for="current_password" class="form-label">Password Saat Ini</label>
                    <input type="password" id="current_password" name="current_password" required
                           class="form-input">
                </div>
            @endif

            <div>
                <label for="password" class="form-label">Password Baru</label>
                <input type="password" id="password" name="password" required
                       class="form-input">
                <p class="text-xs text-slate-500 mt-1">Minimal 8 karakter</p>
            </div>

            <div>
                <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="form-input">
            </div>

            <button type="submit" class="btn-primary w-full justify-center">
                Simpan Password Baru
            </button>
        </form>
    </div>
</div>
@endsection
