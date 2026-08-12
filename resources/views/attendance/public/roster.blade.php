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
    /*
     * Kode margin H T S I A — huruf yang sama dengan Attendance::KODE, rekap,
     * dan cetakan. Yang terpilih jadi tinta pekat; sisanya diam.
     *
     * Sebelumnya tiap status punya warnanya sendiri (emerald, oranye, kuning,
     * biru langit, merah). Pada layar ponsel selebar 340px, lima tombol
     * berwarna dikali 32 baris berarti 160 kotak berwarna dalam satu daftar,
     * dan yang harus dipindai petugas justru baris yang BELUM ditandai.
     */
    $gaya = [
        'hadir' => ['label' => 'Hadir', 'kode' => 'H', 'teks' => 'text-emerald-700'],
        'terlambat' => ['label' => 'Terlambat', 'kode' => 'T', 'teks' => 'text-slate-700'],
        'sakit' => ['label' => 'Sakit', 'kode' => 'S', 'teks' => 'text-amber-700'],
        'izin' => ['label' => 'Izin', 'kode' => 'I', 'teks' => 'text-amber-700'],
        'alfa' => ['label' => 'Alfa', 'kode' => 'A', 'teks' => 'text-rose-700'],
    ];
@endphp

@if ($students->isEmpty())
    <div class="rounded-lg border border-slate-200 bg-white p-6 text-center">
        <h1 class="text-lg font-semibold tracking-tight text-slate-900">Belum ada data siswa</h1>
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
    <div class="sticky top-0 z-20 -mx-4 mb-3 border-b border-slate-200 bg-slate-50/95 px-4 py-3 backdrop-blur">
        <div class="grid grid-cols-5 gap-1.5 text-center">
            @foreach ($gaya as $key => $g)
                <div class="rounded border border-slate-200 bg-white py-1.5">
                    <p class="angka text-lg font-semibold leading-none {{ $g['teks'] }}" x-text="jumlah.{{ $key }}">0</p>
                    <p class="mt-1 font-mono text-[9px] uppercase tracking-wider text-slate-400">{{ $g['kode'] }}</p>
                </div>
            @endforeach
        </div>

        <p class="mt-2 text-center text-xs" x-cloak>
            <span x-show="sisa > 0" class="font-medium text-slate-600">
                <span x-text="sisa"></span> siswa belum ditandai
            </span>
            <span x-show="sisa === 0" class="font-medium text-emerald-700">
                Semua {{ $students->count() }} siswa sudah ditandai
            </span>
        </p>
    </div>

    {{-- Jalan cepat: tandai semua hadir, lalu ubah yang tidak masuk saja.
         Sengaja TIDAK ada nilai bawaan pada tiap baris — daftar yang otomatis
         terisi "hadir" adalah cara termudah absensi jadi asal-asalan. --}}
    <div class="mb-3 flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2.5">
        <button type="button"
                x-on:click="tandaiSemuaHadir()"
                class="btn-secondary h-10 flex-1">Tandai semua hadir</button>
        <button type="button"
                x-on:click="kosongkan()"
                class="btn-ghost h-10">Kosongkan</button>
    </div>

    @error('attendance')
        <p class="alert alert--danger mb-3" role="alert">{{ $message }}</p>
    @enderror

    {{-- Daftar siswa, dibaca seperti buku absensi: nomor, nama, lalu kolom
         status. Nomor urut dipakai karena petugas memanggil per nomor absen. --}}
    <ul class="space-y-2">
        @foreach ($students as $s)
            @php $terpilih = old('attendance.'.$s->id); @endphp
            <li class="rounded-lg border border-slate-200 bg-white p-3"
                x-data="{ status: @js($terpilih) }"
                x-on:roster-isi.window="status = $event.detail">

                <div class="flex items-start gap-3">
                    <span class="mt-0.5 w-6 shrink-0 text-right font-mono text-xs text-slate-400">{{ $loop->iteration }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium leading-snug text-slate-900">{{ $s->name }}</p>
                        @if ($s->nis)
                            <p class="mt-0.5 font-mono text-[10px] text-slate-400">NIS {{ $s->nis }}</p>
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
                                <span class="flex flex-col items-center gap-0.5 rounded border border-slate-200 px-0.5 py-1.5 text-center leading-tight text-slate-500 transition-colors
                                             peer-hover:bg-slate-50 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500
                                             peer-checked:border-slate-900 peer-checked:bg-slate-900 peer-checked:text-white">
                                    <span class="font-mono text-xs font-medium">{{ $g['kode'] }}</span>
                                    <span class="text-[10px] font-medium">{{ $g['label'] }}</span>
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
                           class="form-input form-input--sm">
                </div>
            </li>
        @endforeach
    </ul>

    {{-- Tombol kirim menempel di bawah supaya tidak perlu menggulir 30 baris
         ke bawah setelah selesai menandai. --}}
    <div class="sticky bottom-0 z-20 -mx-4 mt-4 border-t border-slate-200 bg-slate-50/95 px-4 py-3 backdrop-blur">
        <button type="submit"
                class="btn-primary h-11 w-full"
                x-bind:disabled="mengirim"
                x-bind:class="mengirim && 'pointer-events-none opacity-60'">
            <span x-show="!mengirim">Kirim absensi</span>
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
