@extends('layouts.public')
@section('title', 'Absensi Terkirim')
@section('heading', 'Absensi '.$session->classroom->name)
@section('step', '3')
@section('content')

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">

        <div class="border-b border-slate-200 px-5 py-6 text-center">
            <span class="kode kode--hadir mx-auto mb-3 h-7 min-w-[1.75rem] text-base" aria-hidden="true">✓</span>
            <h1 class="text-lg font-semibold tracking-tight text-slate-900">Absensi terkirim</h1>
            <p class="mx-auto mt-1.5 max-w-xs text-sm leading-relaxed text-slate-600">
                Kehadiran kelas {{ $session->classroom->name }} sudah tercatat. Wali kelas bisa melihatnya sekarang.
            </p>
        </div>

        {{-- Bukti tanda terima. Petugas sering diminta membuktikan bahwa ia
             sudah mengisi, jadi angka dan jamnya ditampilkan apa adanya agar
             satu tangkapan layar sudah cukup. --}}
        <dl class="divide-y divide-slate-200 text-sm">
            <div class="flex items-baseline justify-between gap-4 px-5 py-2.5">
                <dt class="text-slate-500">Hadir</dt>
                <dd class="angka text-base font-semibold text-emerald-700">{{ $session->hadir_count }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-4 px-5 py-2.5">
                <dt class="text-slate-500">Tidak hadir</dt>
                <dd class="angka text-base font-semibold text-slate-900">{{ $session->absen_count }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-4 px-5 py-2.5">
                <dt class="text-slate-500">Tanggal</dt>
                <dd class="font-medium text-slate-800">{{ $session->session_date->translatedFormat('l, d M Y') }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-4 px-5 py-2.5">
                <dt class="text-slate-500">Waktu kirim</dt>
                <dd class="angka font-medium text-slate-800">
                    {{ ($session->submitted_at ?? now())->format('H:i') }} WIB
                </dd>
            </div>
        </dl>

        <div class="border-t border-slate-200 bg-slate-50 px-5 py-3">
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
