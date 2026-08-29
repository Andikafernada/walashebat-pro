@php
    $jenis = [
        'success' => ['M4.5 12.5l5 5 10-11', 'polite'],
        'error'   => ['M12 7v6m0 4h.01', 'assertive'],
        'warning' => ['M12 7v6m0 4h.01', 'polite'],
        'info'    => ['M12 16v-5h-1m1-4h.01', 'polite'],
    ];
@endphp

@foreach ($jenis as $kunci => [$jalur, $kesegeraan])
    @if (session($kunci))
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-emerald-200 border-l-4 border-l-emerald-600 bg-white p-4 shadow-xs"
             role="alert" aria-live="{{ $kesegeraan }}">
            <div class="w-7 h-7 rounded-xl bg-emerald-100 text-emerald-950 border border-emerald-200 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="{{ $jalur }}"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs sm:text-sm font-bold text-slate-900 leading-relaxed">{{ session($kunci) }}</p>
            </div>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="mb-5 flex items-start gap-3 rounded-2xl border border-emerald-200 border-l-4 border-l-slate-900 bg-white p-4 shadow-xs"
         role="alert" aria-live="assertive">
        <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-900 border border-slate-300 flex items-center justify-center text-xs font-extrabold shrink-0 mt-0.5">
            !
        </div>
        <div class="min-w-0 flex-1 space-y-1.5">
            <p class="text-xs sm:text-sm font-extrabold text-slate-900">Perhatian: Isian belum bisa disimpan</p>
            <ul class="text-xs text-slate-800 space-y-1 font-medium">
                @foreach ($errors->all() as $galat)
                    <li class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-900 shrink-0"></span>
                        <span>{{ $galat }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
