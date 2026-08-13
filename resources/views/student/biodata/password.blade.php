@extends('layouts.student')
@section('title', 'Ubah Password')

@section('content')
<div class="p-6 lg:p-8 space-y-6">
    <div class="card flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Ubah Password</h1>
            <p class="text-slate-500 mt-1">Pastikan password baru mudah diingat tapi sulit ditebak</p>
        </div>
        <a href="{{ route('student.biodata') }}" class="btn-secondary btn-secondary--sm">Batal</a>
    </div>

    @if ($errors->any())
        <div class="alert alert--danger">
            <div class="alert__body">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="card max-w-md">
        <form action="{{ route('student.biodata.password.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="form-label">Password Saat Ini</label>
                <input type="password" name="current_password" required class="form-input">
            </div>
            <div>
                <label class="form-label">Password Baru</label>
                <input type="password" name="password" required minlength="8" class="form-input">
                <p class="text-xs text-slate-400 mt-1">Minimal 8 karakter.</p>
            </div>
            <div>
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" required minlength="8" class="form-input">
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <a href="{{ route('student.biodata') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary">Ubah Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
