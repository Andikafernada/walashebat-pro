@extends('layouts.student')
@section('title', 'Ubah Password')

@section('content')
<div class="p-6 lg:p-8 space-y-6">
    <div class="glass-card flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Ubah Password</h1>
            <p class="text-slate-500 mt-1">Pastikan password baru mudah diingat tapi sulit ditebak</p>
        </div>
        <a href="{{ route('student.biodata') }}" class="btn btn-secondary">Batal</a>
    </div>

    @if ($errors->any())
        <div class="glass-card border border-red-200 bg-red-50">
            <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="glass-card max-w-md">
        <form action="{{ route('student.biodata.password.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium mb-1">Password Saat Ini</label>
                <input type="password" name="current_password" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Password Baru</label>
                <input type="password" name="password" required minlength="8" class="w-full px-3 py-2 border rounded-lg">
                <p class="text-xs text-slate-400 mt-1">Minimal 8 karakter.</p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" required minlength="8" class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <a href="{{ route('student.biodata') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Ubah Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
