@extends('layouts.app')

@section('title', 'Pengumuman ke Guru')

@section('content')
<div class="max-w-xl space-y-6 pb-12">
    <div>
        <a href="{{ route('admin.dashboard') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">‹ Panel Operator</a>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Pengumuman ke Semua Guru</h1>
        <p class="mt-1 text-xs text-slate-500">
            Muncul di lonceng notifikasi {{ number_format($jumlahGuru, 0, ',', '.') }} guru saat mereka membuka aplikasi.
            Tidak dikirim ke WhatsApp.
        </p>
    </div>

    @include('partials.flash')

    <form method="POST" action="{{ route('admin.announcements.send') }}"
          onsubmit="return confirm('Kirim pengumuman ini ke {{ number_format($jumlahGuru, 0, ',', '.') }} guru?')"
          class="space-y-4 card">
        @csrf
        <div>
            <label for="title" class="block text-xs font-semibold text-slate-700">Judul</label>
            <input type="text" id="title" name="title" maxlength="120" required value="{{ old('title') }}"
                   class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                   placeholder="mis. Pemeliharaan Sabtu malam">
        </div>
        <div>
            <label for="body" class="block text-xs font-semibold text-slate-700">Isi</label>
            <textarea id="body" name="body" rows="5" maxlength="2000" required
                      class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                      placeholder="Tulis pengumuman...">{{ old('body') }}</textarea>
        </div>
        <button type="submit" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-amber-600">
            Kirim ke {{ number_format($jumlahGuru, 0, ',', '.') }} guru
        </button>
    </form>
</div>
@endsection
