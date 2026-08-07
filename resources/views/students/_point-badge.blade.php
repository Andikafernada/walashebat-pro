{{--
    Badge poin disiplin.

    Poin mulai dari 100 lalu berkurang setiap pelanggaran, jadi angka besar
    berarti baik. Nama kelas warna ditulis utuh di setiap cabang, bukan
    disusun lewat interpolasi, supaya Tailwind tidak membuangnya saat purge.
--}}
@php $nada = $siswa->disciplineTone(); @endphp

@if ($nada === 'emerald')
    <span class="badge badge--emerald" title="Tidak ada catatan pelanggaran berarti">
        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ $siswa->discipline_points }}
    </span>
@elseif ($nada === 'amber')
    <span class="badge badge--amber" title="Sudah ada beberapa pelanggaran">
        {{ $siswa->discipline_points }}
    </span>
@else
    <span class="badge badge--rose" title="Poin rendah, perlu perhatian">
        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        {{ $siswa->discipline_points }}
    </span>
@endif
