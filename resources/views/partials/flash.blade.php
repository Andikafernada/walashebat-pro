@php
    /*
     * Empat jenis pesan kilat yang dulu ditulis sebagai empat blok hampir
     * identik sepanjang 45 baris. Yang berbeda cuma warna, ikon, dan seberapa
     * mendesak pembaca layar harus menyelanya.
     *
     * assertive HANYA untuk galat: ia memotong apa pun yang sedang dibacakan.
     * Kabar berhasil tidak berhak melakukan itu.
     */
    $jenis = [
        'success' => ['alert--success', 'M4.5 12.5l5 5 10-11', 'polite'],
        'error'   => ['alert--danger',  'M12 7v6m0 4h.01', 'assertive'],
        'warning' => ['alert--warning', 'M12 7v6m0 4h.01', 'polite'],
        'info'    => ['alert--info',    'M12 16v-5h-1m1-4h.01', 'polite'],
    ];
@endphp

@foreach ($jenis as $kunci => [$gaya, $jalur, $kesegeraan])
    @if (session($kunci))
        <div class="alert {{ $gaya }} mb-5" role="alert" aria-live="{{ $kesegeraan }}">
            <svg class="alert__icon mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                <path d="{{ $jalur }}"/>
                @if ($kunci !== 'success')<circle cx="12" cy="12" r="9"/>@endif
            </svg>
            <p class="alert__body font-medium">{{ session($kunci) }}</p>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="alert alert--danger mb-5" role="alert" aria-live="assertive">
        <svg class="alert__icon mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/>
        </svg>
        <div>
            <p class="alert__title">Isian belum bisa disimpan</p>
            <ul class="alert__body mt-1.5 space-y-1">
                @foreach ($errors->all() as $galat)
                    <li class="flex gap-2">
                        <span class="kode kode--alfa mt-px" aria-hidden="true">!</span>
                        <span>{{ $galat }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
