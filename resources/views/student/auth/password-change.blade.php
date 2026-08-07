@extends('layouts.student')
@section('title', 'Ubah Password')

@section('content')
<div class="p-6 lg:p-8 max-w-lg mx-auto">
    <div class="glass-card">
        <div class="text-center mb-6">
            <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-slate-900">Ubah Password</h1>
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
            <div class="mb-4 p-4 rounded-lg bg-red-50 text-red-700 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('student.password.change') }}" method="POST" class="space-y-4">
            @csrf

            @if($must_change)
                <input type="hidden" name="current_password" value="none">
            @else
                <div>
                    <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1">Password Saat Ini</label>
                    <input type="password" id="current_password" name="current_password" required
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            @endif

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password Baru</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <p class="text-xs text-slate-500 mt-1">Minimal 8 karakter</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Password Baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700 transition-colors">
                Simpan Password Baru
            </button>
        </form>
    </div>
</div>
@endsection
