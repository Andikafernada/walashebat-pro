@extends('layouts.app')

@section('title', 'Buku Kas - ' . $classroom->name)

@section('content')
@php
    $students = $classroom->students()->orderBy('name')->get();
@endphp

<div class="space-y-6 pb-12">
    <!-- Header Bar -->
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-slate-600">{{ $classroom->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Buku Kas</span>
            </nav>
            <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Buku Kas Kelas {{ $classroom->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">Pencatatan uang kas masuk dan keluar beserta laporannya.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Buku besar ini urut waktu dan bercampur masuk/keluar — bentuk
                 yang tepat untuk mempertanggungjawabkan saldo, tetapi tidak
                 bisa menjawab "siapa yang belum bayar?" tanpa dijumlahkan
                 sendiri per anak. --}}
            <a href="{{ route('classes.cashbook.per-siswa', $classroom) }}" class="btn-secondary btn-secondary--sm">Kas per Siswa</a>
            <a href="{{ route('classes.exports.cashbook.excel', $classroom) }}" class="btn-secondary btn-secondary--sm">Excel</a>
            <a href="{{ route('classes.exports.cashbook.pdf', $classroom) }}" target="_blank" class="btn-secondary btn-secondary--sm">Cetak PDF</a>
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $classroom])

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Balance Banner Card -->
    <dl class="deret-angka">
        <div>
            <dt class="stat-label">Saldo kas saat ini</dt>
            <dd class="stat-value {{ $balance < 0 ? 'text-rose-700' : 'text-emerald-700' }}">Rp {{ number_format($balance, 0, ',', '.') }}</dd>
            <p class="stat-sub">Posisi terkini Kelas {{ $classroom->name }}</p>
        </div>
        <div>
            <dt class="stat-label">Transaksi tercatat</dt>
            <dd class="stat-value">{{ $entries->total() }}</dd>
        </div>
    </dl>

    <!-- Main Two-Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <!-- LEFT COLUMN: Cash Transactions List (2/3 width) -->
        <div class="space-y-4 lg:col-span-2">
            <div class="card space-y-4">

                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Mutasi Transaksi Kas</h3>
                        <p class="text-xs text-slate-500">Daftar riwayat pemasukan dan pengeluaran</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-400">{{ $entries->total() }} Transaksi</span>
                </div>

                @if ($entries->isNotEmpty())
                    <div class="divide-y divide-slate-200">
                        @foreach ($entries as $e)
                            <div class="flex items-center justify-between gap-4 py-3 hover:bg-slate-50 px-2 rounded transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded font-semibold text-xs {{ $e->type === 'in' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200' }}">
                                        {{ $e->type === 'in' ? '+' : '-' }}
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-sm text-slate-900">{{ $e->description }}</h4>
                                        <p class="mt-1 text-sm text-slate-500">
                                            <span class="font-mono text-slate-400">{{ $e->transaction_date->format('d/m/Y') }}</span>
                                            @if($e->student)
                                                &middot; Siswa: {{ $e->student->name }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="font-semibold text-sm font-mono {{ $e->type === 'in' ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $e->type === 'in' ? '+' : '-' }} Rp {{ number_format($e->amount, 0, ',', '.') }}
                                    </span>

                                    <form method="POST" action="{{ route('classes.cashbook.destroy', [$classroom, $e]) }}"
                                          onsubmit="return confirm('Hapus transaksi &quot;{{ $e->description }}&quot;?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="h-8 w-8 rounded-lg border border-slate-200 hover:bg-rose-50 hover:text-rose-600 flex items-center justify-center text-slate-400 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($entries->hasPages())
                        <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                            <span>Menampilkan {{ $entries->firstItem() }}–{{ $entries->lastItem() }} dari {{ $entries->total() }} transaksi</span>
                            <div>{{ $entries->links() }}</div>
                        </div>
                    @endif
                @else
                    <div class="my-10 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500 mb-3">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-800">Belum Ada Transaksi Kas</p>
                        <p class="mt-1 text-xs text-slate-500">Input transaksi pemasukan kas mingguan atau pengeluaran kelas di form sebelah kanan.</p>
                    </div>
                @endif

            </div>
        </div>

        <!-- RIGHT COLUMN: Add Form (1/3 width) -->
        <div class="space-y-4">
            {{--
                Pengingat iuran bulanan ke grup WhatsApp orang tua.

                Isinya teks bebas dan TIDAK menyebut siapa yang belum membayar.
                Menyebut nama anak yang menunggak di grup yang dibaca seluruh
                orang tua adalah keputusan yang jauh lebih besar daripada
                teknisnya — kalau suatu saat memang diinginkan, itu percakapan
                tersendiri, bukan kotak centang.
            --}}
            <div class="card space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded bg-emerald-100 text-emerald-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Pengingat Iuran Bulanan</h3>
                        <p class="text-xs text-slate-500">Terkirim otomatis ke grup WhatsApp orang tua</p>
                    </div>
                </div>

                @if (! $classroom->parent_group_wa)
                    {{-- Tanpa grup, kotak centang ini hanya janji yang tidak
                         pernah ditepati dan tidak ada galat yang menjelaskannya. --}}
                    <p class="rounded border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        Grup WhatsApp orang tua kelas ini belum diatur, jadi pengingat belum bisa dikirim.
                        Aturlah lebih dulu di <a href="{{ route('whatsapp.index') }}" class="font-semibold underline">Integrasi WhatsApp</a>.
                    </p>
                @else
                    <form method="POST" action="{{ route('classes.cashbook.pengingat', $classroom) }}" class="space-y-3">
                        @csrf

                        <label class="flex items-center gap-2.5 rounded border border-slate-200 bg-slate-50 p-3">
                            <input type="hidden" name="spp_pengingat_aktif" value="0">
                            <input type="checkbox" name="spp_pengingat_aktif" value="1"
                                   @checked($classroom->spp_pengingat_aktif)
                                   class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-semibold text-slate-800">Kirim pengingat tiap bulan</span>
                        </label>

                        <div>
                            <label for="spp_pengingat_tanggal" class="block eyebrow mb-1">Tanggal kirim</label>
                            <input id="spp_pengingat_tanggal" type="number" name="spp_pengingat_tanggal" min="1" max="31"
                                   value="{{ old('spp_pengingat_tanggal', $classroom->spp_pengingat_tanggal) }}"
                                   class="h-9 w-24 rounded border border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-800 focus:border-emerald-500 focus:outline-none">
                            <span class="text-[11px] text-slate-500">tiap bulan, pukul 07.00</span>
                            {{-- Bulan pendek tidak punya tanggal 31; pengingatnya
                                 jatuh ke hari terakhir alih-alih hilang. --}}
                            <p class="mt-1 text-[11px] text-slate-400">Bila bulannya lebih pendek, dikirim pada hari terakhir bulan itu.</p>
                        </div>

                        <div>
                            <label for="spp_pengingat_teks" class="block eyebrow mb-1">Isi pesan</label>
                            <textarea id="spp_pengingat_teks" name="spp_pengingat_teks" rows="6" maxlength="1000"
                                      placeholder="Kosongkan untuk memakai pesan bawaan."
                                      class="form-input form-input--sm">{{ old('spp_pengingat_teks', $classroom->spp_pengingat_teks) }}</textarea>
                            <p class="mt-1 text-[11px] text-slate-500">
                                Bisa dipakai: <code>{nama_kelas}</code> <code>{bulan}</code> <code>{tahun}</code> <code>{wali_kelas}</code>
                            </p>
                        </div>

                        @if ($classroom->spp_pengingat_terkirim_pada)
                            <p class="text-[11px] text-slate-500">
                                Terakhir terkirim {{ $classroom->spp_pengingat_terkirim_pada->translatedFormat('d F Y') }}.
                            </p>
                        @endif

                        <button type="submit" class="h-9 w-full rounded bg-emerald-600 text-xs font-semibold text-white transition-colors hover:bg-emerald-700">
                            Simpan pengaturan pengingat
                        </button>
                    </form>
                @endif
            </div>

            <div class="card space-y-4">

                <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
                    <div class="stat-icon">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Catat Transaksi Kas</h3>
                        <p class="text-xs text-slate-500">Input Pemasukan / Pengeluaran</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('classes.cashbook.store', $classroom) }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="type" class="form-label">Jenis Transaksi</label>
                        <select id="type" name="type" required class="form-input form-input--sm">
                            <option value="in">Pemasukan (Kas Masuk)</option>
                            <option value="out">Pengeluaran (Kas Keluar)</option>
                        </select>
                    </div>

                    <div>
                        <label for="amount" class="form-label">Jumlah (Rp)</label>
                        <input type="number" id="amount" name="amount" value="{{ old('amount') }}" placeholder="cth: 5000" min="1" required class="form-input form-input--sm">
                    </div>

                    <div>
                        <label for="transaction_date" class="form-label">Tanggal Transaksi</label>
                        <input type="date" id="transaction_date" name="transaction_date" value="{{ date('Y-m-d') }}" required class="form-input form-input--sm">
                    </div>

                    <div>
                        <label for="description" class="form-label">Keterangan / Keperluan</label>
                        <input type="text" id="description" name="description" value="{{ old('description') }}" placeholder="cth: Kas Mingguan, Beli Spidol..." required class="form-input form-input--sm">
                    </div>

                    <div>
                        <label for="student_id" class="form-label">Siswa Pembayar <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                        <select id="student_id" name="student_id" class="form-input form-input--sm">
                            <option value="">— Umum / Tidak Ditentukan —</option>
                            @foreach ($students as $st)
                                <option value="{{ $st->id }}" @selected(old('student_id') == $st->id)>{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="btn-primary w-full">
                        <template x-if="!loading">
                            <span class="flex items-center gap-1.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Simpan Transaksi
                            </span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-1.5">
                                <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                Menyimpan...
                            </span>
                        </template>
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection
