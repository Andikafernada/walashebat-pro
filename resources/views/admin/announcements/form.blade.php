@extends('layouts.app-operator')

@section('title', 'Pengumuman ke Guru')

@section('content')
<div class="max-w-xl space-y-6 pb-12">
    <div>
        <a href="{{ route('admin.dashboard') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800">‹ Panel Operator</a>
        <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Pengumuman ke Semua Guru</h1>
        <p class="mt-1 text-xs text-slate-500">
            Muncul di lonceng notifikasi {{ number_format($jumlahGuru, 0, ',', '.') }} guru saat mereka membuka aplikasi.
            Tidak dikirim ke WhatsApp.
        </p>
    </div>

    @include('partials.flash')

    <form method="POST" action="{{ route('admin.announcements.send') }}"
          onsubmit="return confirm('Kirim pengumuman ini ke {{ number_format($jumlahGuru, 0, ',', '.') }} guru?')"
          class="space-y-4 bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
        @csrf
        <div>
            <label for="title" class="block text-xs font-semibold text-slate-700 mb-1">Judul</label>
            <input type="text" id="title" name="title" maxlength="120" required value="{{ old('title') }}"
                   class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                   placeholder="mis. Pemeliharaan Sabtu malam">
        </div>
        <div>
            <label for="body" class="block text-xs font-semibold text-slate-700 mb-1">Isi</label>
            <textarea id="body" name="body" rows="5" maxlength="2000" required
                      class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                      placeholder="Tulis pengumuman...">{{ old('body') }}</textarea>
        </div>
        <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors shadow-xs">
            Kirim ke {{ number_format($jumlahGuru, 0, ',', '.') }} Guru
        </button>
    </form>
</div>
@endsection
