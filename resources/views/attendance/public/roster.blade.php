@extends('layouts.public')
@section('title', 'Isi Absensi')
@section('heading', 'Absensi '.$session->classroom->name)
@section('step', '2')
@section('content')

@php
    // Warna status dipakai di tiga tempat (tombol, ringkasan, halaman selesai),
    // jadi didefinisikan sekali di sini.
    /*
     * Kelas Tailwind DITULIS UTUH, tidak dirangkai dengan str_replace atau
     * penggabungan string. Pemindai Tailwind membaca berkas ini sebagai teks
     * biasa: nama kelas yang baru terbentuk saat runtime tidak akan pernah
     * ikut ter-compile, dan tombolnya tampil tanpa warna sama sekali.
     */
    $gaya = [
        'hadir' => [
            'label' => 'Hadir',
            'aktif' => 'peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-focus-visible:ring-emerald-500/40',
            'teks' => 'text-emerald-700',
        ],
        'terlambat' => [
            'label' => 'Terlambat',
            'aktif' => 'peer-checked:border-orange-500 peer-checked:bg-orange-500 peer-checked:text-white peer-focus-visible:ring-orange-500/40',
            'teks' => 'text-orange-700',
        ],
        'sakit' => [
            'label' => 'Sakit',
            'aktif' => 'peer-checked:border-amber-500 peer-checked:bg-amber-500 peer-checked:text-white peer-focus-visible:ring-amber-500/40',
            'teks' => 'text-amber-700',
        ],
        'izin' => [
            'label' => 'Izin',
            'aktif' => 'peer-checked:border-sky-600 peer-checked:bg-sky-600 peer-checked:text-white peer-focus-visible:ring-sky-500/40',
            'teks' => 'text-sky-700',
        ],
        'alfa' => [
            'label' => 'Alfa',
            'aktif' => 'peer-checked:border-rose-600 peer-checked:bg-rose-600 peer-checked:text-white peer-focus-visible:ring-rose-500/40',
            'teks' => 'text-rose-700',
        ],
    ];
@endphp

@if ($students->isEmpty())
    <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <h1 class="text-lg font-bold text-slate-900">Belum ada data siswa</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">
            Daftar siswa kelas {{ $session->classroom->name }} masih kosong, jadi absensi belum bisa diisi.
            Sampaikan ke wali kelas untuk melengkapi data siswa.
        </p>
    </div>
@else
<form method="POST"
      action="{{ route('magic.submit', $session->token) }}"
      {{-- @csrf WAJIB. Tanpa ini setiap kiriman ditolak sebagai 419 Page
           Expired, dan petugas melihat halaman galat tepat setelah selesai
           menandai seluruh siswa. --}}
      x-data="rosterAbsensi({{ $students->count() }})"
      x-on:change="hitung()"
      x-on:submit="mengirim = true">
    @csrf

    {{-- Papan hitung. Angka inilah yang harus dilaporkan petugas, jadi ia
         menempel di atas dan ikut berubah setiap kali satu siswa ditandai. --}}
    <div class="sticky top-0 z-20 -mx-4 mb-3 border-b border-slate-200 bg-slate-100/95 px-4 py-3 backdrop-blur">
        <div class="grid grid-cols-5 gap-1.5 text-center">
            @foreach ($gaya as $key => $g)
                <div class="rounded-xl border border-slate-200 bg-white py-2">
                    <p class="text-xl font-extrabold tabular-nums leading-none {{ $g['teks'] }}" x-text="jumlah.{{ $key }}">0</p>
                    <p class="mt-1 text-[9px] font-semibold uppercase tracking-wide text-slate-400">{{ $g['label'] }}</p>
                </div>
            @endforeach
        </div>

        <p class="mt-2 text-center text-xs" x-cloak>
            <span x-show="sisa > 0" class="font-semibold text-slate-600">
                <span x-text="sisa"></span> siswa belum ditandai
            </span>
            <span x-show="sisa === 0" class="font-semibold text-emerald-700">
                Semua {{ $students->count() }} siswa sudah ditandai
            </span>
        </p>
    </div>

    {{-- Jalan cepat: tandai semua hadir, lalu ubah yang tidak masuk saja.
         Sengaja TIDAK ada nilai bawaan pada tiap baris — daftar yang otomatis
         terisi "hadir" adalah cara termudah absensi jadi asal-asalan. --}}
    <div class="mb-4 flex items-center gap-2 rounded-2xl border border-slate-200 bg-white p-3">
        <button type="button"
                x-on:click="tandaiSemuaHadir()"
                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200 transition hover:bg-emerald-100 active:bg-emerald-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            Tandai semua hadir
        </button>
        <button type="button"
                x-on:click="kosongkan()"
                class="rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
            Kosongkan
        </button>
    </div>

    @error('attendance')
        <p class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 p-3 text-sm font-medium text-rose-700" role="alert">
            {{ $message }}
        </p>
    @enderror

    {{-- Daftar siswa, dibaca seperti buku absensi: nomor, nama, lalu kolom
         status. Nomor urut dipakai karena petugas memanggil per nomor absen. --}}
    <ul class="space-y-2">
        @foreach ($students as $s)
            @php $terpilih = old('attendance.'.$s->id); @endphp
            <li class="rounded-2xl border border-slate-200 bg-white p-3"
                x-data="{ status: @js($terpilih) }"
                x-on:roster-isi.window="status = $event.detail">

                <div class="flex items-start gap-3">
                    <span class="mt-0.5 w-6 shrink-0 text-right text-xs font-bold tabular-nums text-slate-400">
                        {{ $loop->iteration }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold leading-snug text-slate-900">{{ $s->name }}</p>
                        @if ($s->nis)
                            <p class="mt-0.5 text-[11px] tabular-nums text-slate-400">NIS {{ $s->nis }}</p>
                        @endif
                    </div>
                </div>

                <fieldset class="mt-2.5">
                    <legend class="sr-only">Status kehadiran {{ $s->name }}</legend>
                    {{-- Lima kolom pada layar ~340px berarti tiap tombol hanya ~62px.
                 Ukuran teks diturunkan agar "Terlambat" tetap terbaca utuh
                 tanpa terpotong. --}}
            <div class="grid grid-cols-5 gap-1">
                        @foreach ($gaya as $key => $g)
                            <label class="cursor-pointer">
                                <input type="radio"
                                       class="peer sr-only"
                                       name="attendance[{{ $s->id }}]"
                                       value="{{ $key }}"
                                       required
                                       x-model="status"
                                       @checked($terpilih === $key)>
                                <span class="block rounded-xl border border-slate-200 px-0.5 py-2 text-center text-[10px] font-semibold leading-tight text-slate-500 transition peer-hover:border-slate-300 peer-hover:bg-slate-50 peer-focus-visible:ring-2 {{ $g['aktif'] }}">
                                    {{ $g['label'] }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                {{-- Catatan hanya muncul untuk siswa yang tidak hadir. Kolom
                     kosong di 30 baris hanya menambah panjang halaman. --}}
                <div x-show="status && status !== 'hadir'" x-cloak class="mt-2.5">
                    <label for="note-{{ $s->id }}" class="sr-only">Catatan untuk {{ $s->name }}</label>
                    <input id="note-{{ $s->id }}"
                           type="text"
                           name="notes[{{ $s->id }}]"
                           maxlength="200"
                           value="{{ old('notes.'.$s->id) }}"
                           placeholder="Keterangan (opsional) — cth: surat dokter"
                           class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/15">
                </div>
            </li>
        @endforeach
    </ul>

    {{-- Tombol kirim menempel di bawah supaya tidak perlu menggulir 30 baris
         ke bawah setelah selesai menandai. --}}
    <div class="sticky bottom-0 z-20 -mx-4 mt-4 border-t border-slate-200 bg-slate-100/95 px-4 py-3 backdrop-blur">
        <button type="submit"
                class="btn-primary w-full py-4 text-base"
                x-bind:disabled="mengirim"
                x-bind:class="mengirim && 'pointer-events-none opacity-60'">
            <span x-show="!mengirim" class="inline-flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Kirim absensi
            </span>
            <span x-show="mengirim" x-cloak class="inline-flex items-center gap-2">
                <span class="spinner spinner--white"></span> Menyimpan…
            </span>
        </button>
        <p class="mt-2 text-center text-[11px] text-slate-500">
            Sekali terkirim, absensi tidak bisa diubah dari tautan ini.
        </p>
    </div>
</form>
@endif
@endsection
