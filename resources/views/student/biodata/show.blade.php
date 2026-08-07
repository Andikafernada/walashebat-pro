@extends('layouts.student')
@section('title', 'Biodata')

@section('content')
<div class="p-6 lg:p-8 space-y-6">
    <!-- Header -->
    <div class="glass-card">
        <h1 class="text-2xl font-bold text-slate-900">Biodata Diri</h1>
        <p class="text-slate-500 mt-1">Data profil dan kontak orang tua</p>
    </div>

    <!-- Profile Card -->
    <div class="glass-card">
        <div class="flex items-center gap-4 mb-6">
            <div class="avatar avatar--2xl avatar--primary">
                <span class="avatar-initials">{{ substr($student->name, 0, 2) }}</span>
            </div>
            <div>
                <h2 class="text-xl font-bold">{{ $student->name }}</h2>
                <p class="text-slate-500">NIS: {{ $student->nis }} • {{ $student->classroom->name }}</p>
            </div>
            <div class="ml-auto flex gap-2">
                <a href="{{ route('student.biodata.password') }}" class="btn btn-secondary">
                    Ubah Password
                </a>
                <a href="{{ route('student.biodata.edit') }}" class="btn btn-secondary">
                    Edit
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-2">Data Pribadi</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Tempat, Tgl Lahir</dt>
                        <dd class="font-medium">{{ $student->tempat_lahir ?? '-' }}, {{ $student->tanggal_lahir?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Agama</dt>
                        <dd class="font-medium">{{ $student->agama ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">No. HP</dt>
                        <dd class="font-medium">{{ $student->phone ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <div>
                <h3 class="text-sm font-medium text-slate-500 mb-2">Data Orang Tua</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Nama Ayah</dt>
                        <dd class="font-medium">{{ $student->nama_ayah ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Nama Ibu</dt>
                        <dd class="font-medium">{{ $student->nama_ibu ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">No. WA Ortu</dt>
                        <dd class="font-medium">{{ $student->parent_phone ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
