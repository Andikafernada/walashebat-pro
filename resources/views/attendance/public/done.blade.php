@extends('layouts.public')
@section('title', 'Absensi Terkirim')
@section('heading', 'Absensi '.$session->classroom->name)
@section('step', '3')
@section('content')

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-100 px-6 py-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50">
                <svg class="h-9 w-9 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Absensi terkirim</h1>
            <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-slate-600">
                Kehadiran kelas {{ $session->classroom->name }} sudah tercatat. Wali kelas bisa melihatnya sekarang.
            </p>
        </div>

        {{-- Bukti tanda terima. Petugas sering diminta membuktikan bahwa ia
             sudah mengisi, jadi angka dan jamnya ditampilkan apa adanya agar
             satu tangkapan layar sudah cukup. --}}
        <dl class="divide-y divide-slate-100 text-sm">
            <div class="flex items-baseline justify-between gap-4 px-6 py-3.5">
                <dt class="text-slate-500">Hadir</dt>
                <dd class="text-base font-bold tabular-nums text-emerald-700">{{ $session->hadir_count }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-4 px-6 py-3.5">
                <dt class="text-slate-500">Tidak hadir</dt>
                <dd class="text-base font-bold tabular-nums text-slate-900">{{ $session->absen_count }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-4 px-6 py-3.5">
                <dt class="text-slate-500">Tanggal</dt>
                <dd class="font-semibold text-slate-800">{{ $session->session_date->translatedFormat('l, d M Y') }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-4 px-6 py-3.5">
                <dt class="text-slate-500">Waktu kirim</dt>
                <dd class="font-semibold tabular-nums text-slate-800">
                    {{ ($session->submitted_at ?? now())->format('H:i') }} WIB
                </dd>
            </div>
        </dl>

        <div class="bg-slate-50 px-6 py-4">
            <p class="text-xs leading-relaxed text-slate-500">
                Ada yang salah isi? Absensi tidak bisa diubah dari tautan ini —
                sampaikan koreksinya ke wali kelas, dan wali kelas bisa memperbaikinya langsung.
            </p>
        </div>
    </div>

    <p class="mt-5 text-center text-xs text-slate-500">
        Tautan ini sudah dipakai dan tidak berlaku lagi. Halaman ini boleh ditutup.
    </p>
@endsection
