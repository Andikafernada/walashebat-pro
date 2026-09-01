@extends('layouts.app')

@section('title', 'Kabar dari Orang Tua — ' . $classroom->name)

@section('content')

@include('partials.class-nav', ['classroom' => $classroom])

<div class="space-y-6 pb-12">

    {{-- ══════════ 1. HEADER & ACTIONS ══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-950 border border-emerald-300">
                    📬 Formulir Online
                </span>
                <span class="text-xs text-slate-500 font-semibold">{{ $periode['label'] }}</span>
            </div>
            <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                Kabar Izin &amp; Sakit dari Orang Tua
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 font-medium">
                Rekap seluruh konfirmasi izin dan sakit yang dikirimkan orang tua murid secara mandiri untuk kelas {{ $classroom->name }}.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('public.excuse.show', $classroom->tokenPublik()) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-all hover:scale-105">
                <span>🔗</span>
                <span>Buka Link Form Ortu</span>
            </a>
        </div>
    </div>

    @include('partials.flash')

    {{-- ══════════ 2. FILTER BULAN & PERIODE ELEGAN ══════════ --}}
    <div class="bg-white rounded-2xl border border-emerald-200 p-3 sm:p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('classes.excuses.index', $classroom) }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="mode" value="bulan">
            <label for="bulan" class="text-xs font-bold text-slate-700">Pilih Periode Bulan:</label>
            <input type="month" id="bulan" name="bulan" 
                   value="{{ request('bulan', $periode['bulan']->format('Y-m')) }}"
                   class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            @if(request('jenis'))
                <input type="hidden" name="jenis" value="{{ request('jenis') }}">
            @endif
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition-colors shadow-2xs">
                Tampilkan
            </button>
        </form>

        {{-- FILTER JENIS (SEMUA / SAKIT / IZIN) --}}
        <div class="flex items-center gap-1.5">
            <a href="{{ request()->fullUrlWithQuery(['jenis' => null]) }}" 
               class="px-2.5 py-1 rounded-xl text-xs font-bold transition-all {{ empty($activeJenis) ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Semua ({{ $stats['total'] }})
            </a>
            <a href="{{ request()->fullUrlWithQuery(['jenis' => 'sakit']) }}" 
               class="px-2.5 py-1 rounded-xl text-xs font-bold transition-all {{ $activeJenis === 'sakit' ? 'bg-amber-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                🏥 Sakit ({{ $stats['sakit'] }})
            </a>
            <a href="{{ request()->fullUrlWithQuery(['jenis' => 'izin']) }}" 
               class="px-2.5 py-1 rounded-xl text-xs font-bold transition-all {{ $activeJenis === 'izin' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                📩 Izin ({{ $stats['izin'] }})
            </a>
        </div>
    </div>

    {{-- ══════════ 3. DAFTAR KABAR DARI ORANG TUA ══════════ --}}
    @if($excuses->isEmpty())
        <div class="rounded-3xl border border-emerald-200 bg-white p-8 sm:p-12 text-center shadow-xs space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 text-2xl flex items-center justify-center mx-auto">
                📬
            </div>
            <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Belum Ada Kabar Izin/Sakit Masuk di Bulan {{ $periode['label'] }}</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                Tautan form izin untuk orang tua: <br>
                <code class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-lg select-all">{{ route('public.excuse.show', $classroom->tokenPublik()) }}</code>
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            @foreach($excuses as $excuse)
                <div class="bg-white rounded-2xl border border-emerald-200/80 p-4 shadow-xs space-y-3 hover:border-emerald-400 transition-all">
                    <div class="flex items-start justify-between gap-2 border-b border-emerald-100 pb-2.5">
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-900 leading-tight">
                                {{ $excuse->student ? $excuse->student->name : 'Siswa Terhapus' }}
                            </h4>
                            <p class="text-[11px] font-semibold text-slate-400 mt-0.5">
                                NIS: {{ $excuse->student?->nis ?? '—' }} &middot; Tgl: <span class="font-bold text-slate-700">{{ $excuse->tanggal->translatedFormat('d F Y') }}</span>
                            </p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $excuse->jenis === 'sakit' ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-indigo-100 text-indigo-900 border border-indigo-300' }}">
                            {{ $excuse->jenis === 'sakit' ? '🏥 Sakit' : '📩 Izin' }}
                        </span>
                    </div>

                    <div class="text-xs text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 leading-relaxed">
                        <span class="font-bold text-slate-500 block text-[10px] uppercase mb-0.5">Alasan / Keterangan:</span>
                        {{ $excuse->keterangan ?: 'Tidak ada catatan tambahan.' }}
                    </div>

                    <div class="flex items-center justify-between pt-1 text-[11px] text-slate-500 font-medium">
                        <span class="flex items-center gap-1">
                            <span>🕒 Dikirim:</span>
                            <span class="font-bold text-slate-700">{{ $excuse->created_at->translatedFormat('d M, H:i') }} WIB</span>
                        </span>

                        @if($excuse->attachment_path)
                            <a href="{{ $excuse->attachmentUrl() }}" target="_blank" 
                               class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold border border-emerald-200 transition-colors">
                                <span>📎</span>
                                <span>Foto Surat</span>
                            </a>
                        @else
                            <span class="text-slate-400 text-[10px]">Tanpa lampiran surat</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-4">
            {{ $excuses->links() }}
        </div>
    @endif

</div>
@endsection
