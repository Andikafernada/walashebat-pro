@extends('layouts.app')

@section('title', 'Daftar Kelas')

@section('content')
<div class="space-y-5 pb-12" x-data="{ shareClassId: '', className: '', shareLink: '', showModal: false }">

    <div class="page-header">
        <div>
            <p class="eyebrow">Daftar kelas</p>
            <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Kelas Anda</h1>
            <p class="mt-1 text-sm text-slate-500">
                Kelas perwalian dan kelas yang Anda ajar sebagai guru mapel, dalam satu tempat.
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if($classes->total() > 0)
                <a href="{{ route('classes.trashed') }}" class="btn-secondary btn-secondary--sm">Arsip</a>
            @endif
            <a href="{{ route('classes.create') }}" class="btn-primary btn-primary--sm">Buat kelas baru</a>
        </div>
    </div>

    @include('partials.flash')

    {{-- Saringan jenis. Baru muncul bila guru memang memegang kedua-duanya —
         menampilkannya pada guru yang hanya punya kelas perwalian cuma
         menambah pilihan yang tidak pernah berguna. --}}
    @if ($jumlahPerwalian > 0 && $jumlahAjar > 0)
        @php
            $saringan = [
                [null, 'Semua', $jumlahPerwalian + $jumlahAjar],
                ['perwalian', 'Perwalian', $jumlahPerwalian],
                ['ajar', 'Guru Mapel', $jumlahAjar],
            ];
        @endphp
        <div class="inline-flex overflow-hidden rounded border border-slate-200 bg-white">
            @foreach ($saringan as [$nilai, $label, $jumlah])
                @php $ini = $jenisDipilih === $nilai; @endphp
                <a href="{{ route('classes.index', $nilai ? ['jenis' => $nilai] : []) }}"
                   @if($ini) aria-current="page" @endif
                   class="flex items-center gap-1.5 border-r border-slate-200 px-3 py-1.5 text-xs font-medium transition-colors last:border-r-0 {{ $ini ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                    <span class="font-mono text-[10px] {{ $ini ? 'text-slate-300' : 'text-slate-400' }}">{{ $jumlah }}</span>
                </a>
            @endforeach
        </div>
    @endif

    @if ($classes->isEmpty())
        <div class="empty-state">
            <p class="empty-state__title">Belum ada kelas terdaftar</p>
            <p class="empty-state__description">Buat kelas untuk mulai mengelola absensi dan biodata siswa.</p>
            <a href="{{ route('classes.create') }}" class="btn-primary empty-state__action">Buat kelas pertama</a>
        </div>
    @else
        {{--
            Daftar bergaris, bukan kartu berjajar tiga kolom.

            Sebelumnya tiap kelas adalah kartu setinggi ~340px berisi ikon
            bergradien, tiga baris statistik yang masing-masing punya kotak dan
            warna sendiri, dan empat tombol. Guru dengan sembilan kelas harus
            menggulir tiga layar penuh untuk melihat kelas terakhirnya, dan
            perbandingan antar kelas — mana yang absensinya belum diisi —
            justru mustahil karena angkanya tidak pernah sejajar.

            Sekarang satu baris satu kelas, angkanya lurus ke bawah dalam
            kolom, dan sembilan kelas muat dalam satu layar.
        --}}
        <div class="blok">
            <div class="blok__kepala">
                <h2 class="blok__judul">{{ $classes->total() }} kelas</h2>
                <span class="eyebrow">Absensi hari ini</span>
            </div>

            <div class="blok__daftar">
                @foreach ($classes as $class)
                    @php
                        /*
                         * Perbedaan perwalian dan kelas ajar dibuat dua lapis —
                         * garis tepi kiri DAN tulisan pada lencana — supaya tetap
                         * terbaca oleh guru yang sulit membedakan warna, dan tetap
                         * jelas pada cetakan hitam-putih.
                         */
                        $ajar = $class->kelasAjar();

                        /*
                         * Kelas perwalian disematkan — tetapi hanya di tab "Semua
                         * Kelas", dan hanya bila guru ini memang juga memegang kelas
                         * ajar.
                         *
                         * Penyematan adalah pernyataan pembanding: "yang ini beda
                         * dari yang lain". Pada guru yang seluruh kelasnya perwalian,
                         * setiap baris akan tersemat — dan penanda yang muncul di
                         * semua tempat berhenti menandakan apa pun.
                         */
                        $tersemat = ! $ajar && $jenisDipilih === null && $jumlahAjar > 0;
                    @endphp
                    <div class="flex flex-col gap-3 border-l-[3px] p-3.5 sm:flex-row sm:items-center {{ $ajar ? 'border-l-emerald-600' : 'border-l-indigo-600' }}">

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('classes.show', $class) }}"
                                   class="text-sm font-semibold tracking-tight text-slate-900 hover:text-indigo-700 hover:underline">
                                    {{ $class->name }}
                                </a>
                                <span class="badge {{ $ajar ? 'badge--emerald' : 'badge--indigo' }}">{{ $ajar ? 'Guru Mapel' : 'Wali Kelas' }}</span>
                                @if ($tersemat)
                                    {{-- Penanda tersemat. Dulu pita berdenyut yang
                                         menempel di tepi kartu, dengan animate-ping
                                         berlapis; sekarang tidak ada animasi sama
                                         sekali di halaman ini, jadi kekhawatiran
                                         lamanya — bahwa guru yang menyalakan
                                         "kurangi gerak" kehilangan penandanya —
                                         tidak lagi punya sisi yang bisa gagal. --}}
                                    <span class="kode kode--aksen">Kelas Wali Anda</span>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                @if ($ajar)
                                    {{ implode(' · ', $class->mapelDiampu()) ?: 'Mapel belum diisi' }}
                                @else
                                    {{ $class->major ?? 'Umum' }} &middot; TA {{ $class->academic_year ?? '2026/2027' }}
                                @endif
                            </p>
                        </div>

                        {{-- Angka-angkanya sejajar ke bawah antar baris: itu
                             satu-satunya cara membandingkan kelas sekilas. --}}
                        <div class="flex shrink-0 items-center gap-4 sm:gap-6">
                            <div class="w-16">
                                <p class="eyebrow">Siswa</p>
                                <p class="angka text-sm font-medium text-slate-900">{{ $class->students_count }}</p>
                            </div>

                            <div class="w-28">
                                <p class="eyebrow">Hari ini</p>
                                @if ($class->has_today_session)
                                    <p class="angka truncate text-sm font-medium text-emerald-700">{{ $class->recent_attendance_text }}</p>
                                @else
                                    <p class="text-sm text-slate-400">Belum ada sesi</p>
                                @endif
                            </div>

                            <div class="hidden w-24 sm:block">
                                @if ($ajar)
                                    <p class="eyebrow">Sesi mapel</p>
                                    <p class="angka text-sm font-medium text-slate-900">
                                        {{ $class->today_session_count }}/{{ max(count($class->mapelDiampu()), 1) }}
                                    </p>
                                @else
                                    <p class="eyebrow">Pelanggaran</p>
                                    <p class="angka text-sm font-medium {{ ($class->violation_count ?? 0) > 0 ? 'text-rose-700' : 'text-slate-900' }}">
                                        {{ $class->violation_count ?? 0 }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <a href="{{ $ajar ? route('classes.attendance.manual.create', $class) : route('classes.attendance.index', $class) }}"
                               class="btn-secondary btn-secondary--sm">{{ $ajar ? 'Absen Manual' : 'Absensi' }}</a>

                            @if ($ajar)
                                <a href="{{ route('classes.jurnal.index', $class) }}" class="btn-ghost btn-ghost--sm">Jurnal Mengajar</a>
                                <a href="{{ route('classes.nilai.index', $class) }}" class="btn-ghost btn-ghost--sm">Nilai Harian</a>
                            @else
                                <button type="button"
                                        @click="shareClassId = '{{ $class->id }}'; className = '{{ $class->name }}'; shareLink = '{{ route('public.biodata.show', $class->tokenPublik()) }}'; showModal = true"
                                        class="btn-ghost btn-ghost--sm">Bagikan Form Mandiri Siswa</button>
                            @endif

                            <a href="{{ route('classes.edit', $class) }}" class="btn-icon" aria-label="Ubah kelas {{ $class->name }}">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($classes->hasPages())
            <div class="flex items-center justify-between gap-3 text-xs text-slate-500">
                <span class="angka">Menampilkan {{ $classes->firstItem() }}–{{ $classes->lastItem() }} dari {{ $classes->total() }} kelas</span>
                <div>{{ $classes->links() }}</div>
            </div>
        @endif
    @endif

    {{-- Modal hanya dibuat bila ada kelas yang bisa memanggilnya. Pada guru yang
         sedang menyaring kelas ajar saja, tidak satu pun baris punya tombol
         "Bagikan form" — modalnya akan jadi markup mati yang tetap menyebut
         biodata di halaman yang justru tidak mengurus biodata. --}}
    @if ($classes->contains(fn ($k) => ! $k->kelasAjar()))
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg border border-slate-300 bg-white shadow-xl" @click.away="showModal = false">
            <div class="flex items-start justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                <div>
                    <h2 class="text-sm font-semibold tracking-tight text-slate-900">Bagikan Form Mandiri Siswa</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Kelas <span x-text="className" class="font-medium text-slate-700"></span></p>
                </div>
                <button type="button" @click="showModal = false" class="btn-icon" aria-label="Tutup">&times;</button>
            </div>

            <div class="space-y-4 p-4">
                <div class="space-y-1.5">
                    <p class="eyebrow">Pratinjau pesan</p>
                    <div class="space-y-2 rounded border border-slate-200 bg-slate-50 p-3 text-xs leading-relaxed text-slate-700">
                        <p class="font-semibold text-slate-900">*PEMBERITAHUAN PENGISIAN BIODATA MANDIRI SISWA*</p>
                        <p>Assalamu'alaikum Wr. Wb. / Selamat Pagi Bapak/Ibu Orang Tua &amp; Wali Siswa,</p>
                        <p>Sehubungan dengan pembaruan data administrasi siswa Kelas <span x-text="className" class="font-medium"></span>, kami memohon kesediaan Bapak/Ibu atau siswa untuk melengkapi Form Biodata Mandiri melalui tautan berikut:</p>
                        <p class="break-all font-mono text-indigo-700" x-text="shareLink"></p>
                        <p class="text-slate-500">Mohon data dapat diisi dengan teliti dan benar. Terima kasih.</p>
                    </div>
                </div>

                <div class="space-y-2 border-t border-slate-200 pt-3">
                    <form :action="'/classes/' + shareClassId + '/share-biodata-wa'" method="POST">
                        @csrf
                        <button type="submit" class="btn-primary w-full">Kirim ke grup WhatsApp</button>
                    </form>

                    <a :href="'https://api.whatsapp.com/send?text=' + encodeURIComponent('*PEMBERITAHUAN PENGISIAN BIODATA MANDIRI SISWA*\nKelas ' + className + '\n\nAssalamu\'alaikum Wr. Wb. / Selamat Pagi Bapak/Ibu Orang Tua & Wali Siswa,\n\nSehubungan dengan pembaruan data administrasi siswa, mohon isi Form Biodata Mandiri melalui tautan resmi:\n' + shareLink + '\n\nMohon diisi dengan benar. Terima kasih.')"
                       target="_blank" class="btn-secondary w-full">Buka di WhatsApp</a>

                    <button type="button" @click="navigator.clipboard.writeText(shareLink); alert('Tautan form siswa berhasil disalin.');"
                            class="btn-ghost w-full">Salin tautan</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
