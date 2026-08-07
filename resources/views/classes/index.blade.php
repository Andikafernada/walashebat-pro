@extends('layouts.app')

@section('title', 'Daftar Kelas')

@section('content')
<div class="space-y-6 pb-12" x-data="{ shareClassId: '', className: '', shareLink: '', showModal: false }">
    <!-- Header Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Daftar Kelas Anda</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Kelas <span class="font-semibold text-indigo-600">perwalian</span> dan kelas yang Anda
                <span class="font-semibold text-teal-600">ajar sebagai guru mapel</span>, dalam satu tempat.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($classes->total() > 0)
                <a href="{{ route('classes.trashed') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Arsip Kelas
                </a>
            @endif
            <a href="{{ route('classes.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-indigo-500/20 hover:bg-indigo-700 active:scale-95 transition-all">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                + Buat Kelas Baru
            </a>
        </div>
    </div>

    <!-- Include Flash Messages -->
    @include('partials.flash')

    {{-- Saringan jenis. Baru muncul bila guru memang memegang kedua-duanya —
         menampilkannya pada guru yang hanya punya kelas perwalian cuma
         menambah pilihan yang tidak pernah berguna. --}}
    @if ($jumlahPerwalian > 0 && $jumlahAjar > 0)
        @php
            $saringan = [
                [null, 'Semua Kelas', $jumlahPerwalian + $jumlahAjar, 'border-slate-800 bg-slate-800 text-white'],
                ['perwalian', '🏫 Perwalian', $jumlahPerwalian, 'border-indigo-300 bg-indigo-600 text-white'],
                ['ajar', '📚 Guru Mapel', $jumlahAjar, 'border-teal-300 bg-teal-600 text-white'],
            ];
        @endphp
        <div class="flex flex-wrap gap-2">
            @foreach ($saringan as [$nilai, $label, $jumlah, $kelasAktif])
                @php $ini = $jenisDipilih === $nilai; @endphp
                <a href="{{ route('classes.index', $nilai ? ['jenis' => $nilai] : []) }}"
                   class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-bold transition-all {{ $ini ? $kelasAktif : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                    <span class="rounded-full px-2 py-0.5 text-[10px] tabular-nums {{ $ini ? 'bg-white/20' : 'bg-slate-100 text-slate-600' }}">{{ $jumlah }}</span>
                </a>
            @endforeach
        </div>
    @endif

    @if ($classes->isEmpty())
        <div class="rounded-2xl border border-slate-200/80 bg-white p-12 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 mb-3">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">Belum ada kelas terdaftar</h3>
            <p class="text-xs text-slate-500 mt-1">Buat kelas baru untuk mulai mengelola absensi dan biodata siswa secara otomatis.</p>
            <div class="mt-4">
                <a href="{{ route('classes.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-indigo-500/20">
                    + Buat Kelas Pertama
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($classes as $class)
                @php
                    /*
                     * Satu sumber warna & kata untuk seluruh kartu.
                     *
                     * Perwalian = indigo, guru mapel = teal. Perbedaannya dibuat
                     * dua lapis — warna DAN tulisan — supaya tetap terbaca oleh
                     * guru yang sulit membedakan warna, dan tetap jelas pada
                     * cetakan hitam-putih.
                     */
                    $ajar = $class->kelasAjar();
                    $gaya = $ajar
                        ? ['pinggir' => 'hover:border-teal-300', 'garis' => 'border-l-4 border-l-teal-500',
                           'gradasi' => 'from-teal-500 to-emerald-600', 'bayang' => 'shadow-teal-500/20',
                           'lencana' => 'bg-teal-50 text-teal-700 border-teal-200', 'tombol' => 'bg-teal-600 hover:bg-teal-700 shadow-teal-500/20',
                           'pranala' => 'hover:text-teal-600', 'label' => 'Guru Mapel']
                        : ['pinggir' => 'hover:border-indigo-300', 'garis' => 'border-l-4 border-l-indigo-500',
                           'gradasi' => 'from-indigo-500 to-purple-600', 'bayang' => 'shadow-indigo-500/20',
                           'lencana' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'tombol' => 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-500/20',
                           'pranala' => 'hover:text-indigo-600', 'label' => 'Wali Kelas'];
                    /*
                     * Kartu perwalian disematkan — tetapi hanya di tab "Semua
                     * Kelas", dan hanya bila guru ini memang juga memegang kelas
                     * ajar.
                     *
                     * Penyematan adalah pernyataan pembanding: "yang ini beda
                     * dari yang lain". Pada guru yang seluruh kelasnya perwalian,
                     * setiap kartu akan tersemat — dan penanda yang muncul di
                     * semua tempat berhenti menandakan apa pun, hanya menyisakan
                     * animasi yang berkedip tanpa maksud.
                     */
                    $tersemat = ! $ajar && $jenisDipilih === null && $jumlahAjar > 0;
                @endphp
                <div class="relative rounded-2xl border {{ $tersemat ? 'border-indigo-300 ring-2 ring-indigo-400/50 shadow-lg shadow-indigo-500/10' : 'border-slate-200/80 shadow-sm' }} {{ $gaya['garis'] }} bg-white p-5 {{ $gaya['pinggir'] }} hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                    @if ($tersemat)
                        {{--
                            Penanda kelas perwalian.

                            Tiga lapis sekaligus, dengan sengaja: cincin indigo di
                            tepi kartu, pita bertuliskan kata "wali kelas", dan
                            titik berdenyut. Animasi saja tidak cukup — guru yang
                            memakai pengaturan "kurangi gerak" di ponselnya tidak
                            akan melihat denyut itu sama sekali, dan yang tersisa
                            harus tetap bisa dibaca.

                            Titiknya memakai animate-ping berlapis, pola yang sama
                            dengan penanda realtime di Beranda.
                        --}}
                        <div class="absolute -top-3 left-4 z-10 inline-flex items-center gap-1.5 rounded-full bg-indigo-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white shadow-md shadow-indigo-500/30">
                            <span class="relative flex h-1.5 w-1.5">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-white"></span>
                            </span>
                            📌 Kelas Wali Anda
                        </div>
                    @endif
                    <div>
                        <!-- Class Header -->
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $gaya['gradasi'] }} text-white font-black shadow-md {{ $gaya['bayang'] }}">
                                    @if ($ajar)
                                        {{-- Buku: yang diajar. --}}
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    @else
                                        {{-- Gedung sekolah: yang diwalikelasi. --}}
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('classes.show', $class) }}" class="font-bold text-base text-slate-900 {{ $gaya['pranala'] }} transition-colors truncate block">
                                        {{ $class->name }}
                                    </a>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="inline-flex items-center rounded-lg border px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $gaya['lencana'] }}">
                                            {{ $gaya['label'] }}
                                        </span>
                                        <span class="text-[11px] text-slate-500 truncate">
                                            @if ($ajar)
                                                {{ implode(' · ', $class->mapelDiampu()) ?: 'Mapel belum diisi' }}
                                            @else
                                                {{ $class->major ?? 'Umum' }} &middot; TA {{ $class->academic_year ?? '2026/2027' }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('classes.edit', $class) }}" class="h-8 w-8 shrink-0 rounded-xl border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                        </div>

                        <!-- Class Stats List (Full-Width Rows, Zero Overflow guaranteed) -->
                        <div class="space-y-2.5 py-3">
                            <!-- Total Siswa -->
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 p-2.5 border border-slate-100 text-xs">
                                <span class="text-slate-600 font-semibold flex items-center gap-2">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                                    Total Siswa
                                </span>
                                <span class="font-black text-sm text-slate-900">{{ $class->students_count }} Siswa</span>
                            </div>

                            <!-- Absensi Hari Ini -->
                            <div class="flex items-center justify-between rounded-xl p-2.5 border text-xs {{ $class->has_today_session ? 'bg-emerald-50/70 border-emerald-200' : 'bg-slate-50 border-slate-100' }}">
                                <span class="font-semibold flex items-center gap-2 {{ $class->has_today_session ? 'text-emerald-800' : 'text-slate-500' }}">
                                    <svg class="h-4 w-4 {{ $class->has_today_session ? 'text-emerald-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Absensi Hari Ini
                                </span>
                                <span class="font-black text-xs px-2.5 py-1 rounded-lg border {{ $class->has_today_session ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                    {{ $class->has_today_session ? $class->recent_attendance_text : 'Belum Sesi' }}
                                </span>
                            </div>

                            @if ($ajar)
                                <!-- Sesi per mapel hari ini -->
                                <div class="flex items-center justify-between rounded-xl bg-teal-50/60 p-2.5 border border-teal-100 text-xs">
                                    <span class="text-teal-900 font-semibold flex items-center gap-2">
                                        <svg class="h-4 w-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Sesi Mengajar Hari Ini
                                    </span>
                                    <span class="font-black text-xs text-teal-800 bg-teal-100/80 px-2.5 py-1 rounded-lg border border-teal-200">
                                        {{ $class->today_session_count }} dari {{ max(count($class->mapelDiampu()), 1) }} mapel
                                    </span>
                                </div>
                            @else
                                <!-- Pelanggaran: catatan wali kelas, bukan guru mapel -->
                                <div class="flex items-center justify-between rounded-xl bg-rose-50/60 p-2.5 border border-rose-100 text-xs">
                                    <span class="text-rose-800 font-semibold flex items-center gap-2">
                                        <svg class="h-4 w-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Pelanggaran Siswa
                                    </span>
                                    <span class="font-black text-xs text-rose-700 bg-rose-100/80 px-2.5 py-1 rounded-lg border border-rose-200">
                                        {{ $class->violation_count ?? 0 }} Kasus
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-2.5 pt-1">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('classes.show', $class) }}" class="flex-1 h-10 rounded-xl {{ $gaya['tombol'] }} active:scale-95 text-white text-xs font-bold transition-all flex items-center justify-center shadow-md">
                                Kelola Kelas &rarr;
                            </a>
                            <a href="{{ $ajar ? route('classes.attendance.manual.create', $class) : route('classes.attendance.index', $class) }}"
                               class="h-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-4 text-xs font-bold text-slate-700 flex items-center shadow-xs whitespace-nowrap">
                                {{ $ajar ? 'Absen Manual' : 'Absensi' }}
                            </a>
                        </div>

                        @if ($ajar)
                            {{-- Guru mapel tidak mengumpulkan biodata: datanya hanya NIS
                                 dan nama. Yang dipakai sehari-hari adalah jurnal
                                 mengajar dan nilai per Capaian Pembelajaran. --}}
                            <div class="flex items-center gap-2">
                                <a href="{{ route('classes.jurnal.index', $class) }}" class="flex-1 h-9 rounded-xl border border-teal-200 bg-teal-50/80 hover:bg-teal-100 text-teal-800 font-bold text-xs flex items-center justify-center gap-1.5 transition-all">
                                    📓 Jurnal Mengajar
                                </a>
                                <a href="{{ route('classes.nilai.index', $class) }}" class="flex-1 h-9 rounded-xl border border-teal-200 bg-teal-50/80 hover:bg-teal-100 text-teal-800 font-bold text-xs flex items-center justify-center gap-1.5 transition-all">
                                    ✅ Nilai Harian
                                </a>
                            </div>
                        @else
                            <!-- Share Form Button -->
                            <button type="button" @click="shareClassId = '{{ $class->id }}'; className = '{{ $class->name }}'; shareLink = '{{ route('public.biodata.show', $class->tokenPublik()) }}'; showModal = true"
                                    class="w-full h-9 rounded-xl border border-indigo-200 bg-indigo-50/80 hover:bg-indigo-100 text-indigo-800 font-bold text-xs flex items-center justify-center gap-2 transition-all">
                                <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                Bagikan Form Mandiri Siswa
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($classes->hasPages())
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Menampilkan {{ $classes->firstItem() }}–{{ $classes->lastItem() }} dari {{ $classes->total() }} kelas</span>
                <div>{{ $classes->links() }}</div>
            </div>
        @endif
    @endif

    {{-- Modal hanya dibuat bila ada kelas yang bisa memanggilnya. Pada guru yang
         sedang menyaring kelas ajar saja, tidak satu pun kartu punya tombol
         "Bagikan Form Mandiri Siswa" — modalnya akan jadi markup mati yang tetap
         menyebut biodata di halaman yang justru tidak mengurus biodata. --}}
    @if ($classes->contains(fn ($k) => ! $k->kelasAjar()))
    <!-- Share Link & Direct WhatsApp Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto" @click.away="showModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Bagikan Form Mandiri Siswa</h3>
                    <p class="text-xs text-slate-500">Kelas <span x-text="className" class="font-bold text-indigo-600"></span></p>
                </div>
                <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-xl">×</button>
            </div>

            <!-- Professional Template Preview -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pratinjau Pesan Profesional WhatsApp:</label>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3.5 text-xs text-slate-800 font-sans space-y-2 leading-relaxed">
                    <p class="font-bold text-emerald-950">*PEMBERITAHUAN PENGISIAN BIODATA MANDIRI SISWA*</p>
                    <p>Assalamu'alaikum Wr. Wb. / Selamat Pagi Bapak/Ibu Orang Tua &amp; Wali Siswa,</p>
                    <p>Sehubungan dengan pembaruan data administrasi siswa Kelas <span x-text="className" class="font-bold"></span>, kami memohon kesediaan Bapak/Ibu atau siswa untuk melengkapi Form Biodata Mandiri melalui tautan berikut:</p>
                    <p class="font-mono text-indigo-700 underline font-bold" x-text="shareLink"></p>
                    <p class="text-slate-500 text-[11px]">Mohon data dapat diisi dengan teliti dan benar. Terima kasih.</p>
                </div>
            </div>

            <!-- Send Action Buttons -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
                <!-- 1-Click Gateway Broadcast Form -->
                <form :action="'/classes/' + shareClassId + '/share-biodata-wa'" method="POST">
                    @csrf
                    <button type="submit" class="w-full h-10 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-all shadow-md shadow-emerald-500/20 flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        🚀 Kirim Otomatis Langsung ke Grup WhatsApp (Gateway)
                    </button>
                </form>

                <!-- WhatsApp Web / App Fallback -->
                <a :href="'https://api.whatsapp.com/send?text=' + encodeURIComponent('*PEMBERITAHUAN PENGISIAN BIODATA MANDIRI SISWA*\nKelas ' + className + '\n\nAssalamu\'alaikum Wr. Wb. / Selamat Pagi Bapak/Ibu Orang Tua & Wali Siswa,\n\nSehubungan dengan pembaruan data administrasi siswa, mohon isi Form Biodata Mandiri melalui tautan resmi:\n' + shareLink + '\n\nMohon diisi dengan benar. Terima kasih.')"
                   target="_blank"
                   class="w-full h-10 rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs flex items-center justify-center gap-2 transition-all">
                    📱 Buka &amp; Kirim via WhatsApp App / Web
                </a>

                <!-- Copy Link Button -->
                <button type="button" @click="navigator.clipboard.writeText(shareLink); alert('Tautan Form Siswa berhasil disalin!');"
                        class="w-full h-9 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center gap-1.5">
                    📋 Salin Tautan Ke Clipboard
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
