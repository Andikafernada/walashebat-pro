@extends('layouts.app')

@section('title', 'Buku Kas - ' . $classroom->name)

@section('content')
@php
    $totalIn = $totalIn ?? $classroom->cashBooks()->where('type', 'in')->sum('amount');
    $totalOut = $totalOut ?? $classroom->cashBooks()->where('type', 'out')->sum('amount');
    $students = $students ?? $classroom->students()->where('is_active', true)->orderBy('name')->get();
@endphp
<div class="space-y-6 pb-12">

    {{-- ══════════ 1. HEADER & ACTION BUTTONS ══════════ --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600 transition-colors">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-slate-600 transition-colors">{{ $classroom->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500 font-medium">Buku Kas</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                Buku Kas Kelas {{ $classroom->name }}
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500">
                Pencatatan uang kas mingguan, iuran kegiatan siswa, dan pembukuan pengeluaran kelas transparan.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ route('classes.cashbook.per-siswa', $classroom) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-950 border border-emerald-200 text-xs font-bold shadow-xs transition-all hover:scale-105">
                <span>👥</span>
                <span>Matriks Iuran Per Siswa</span>
            </a>

            @if(Route::has('classes.exports.cashbook.excel'))
                <a href="{{ route('classes.exports.cashbook.excel', $classroom) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all">
                    <span>📊</span>
                    <span>Excel</span>
                </a>
            @endif

            @if(Route::has('classes.exports.cashbook.pdf'))
                <a href="{{ route('classes.exports.cashbook.pdf', $classroom) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all">
                    <span>📄</span>
                    <span>Cetak PDF</span>
                </a>
            @endif
        </div>
    </div>

    {{-- NAVIGASI KELAS --}}
    @include('partials.class-nav', ['classroom' => $classroom])

    @include('partials.flash')

    {{-- ══════════ 2. FINANCIAL STATS (Bright Soft Emerald Gradient) ══════════ --}}
    <dl class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">

        {{-- Saldo Saat Ini --}}
        <div class="bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-700 text-white rounded-3xl p-5 shadow-lg shadow-emerald-600/15 col-span-2 sm:col-span-1 space-y-2 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/15 rounded-full blur-xl pointer-events-none"></div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-100 uppercase tracking-wider text-[10.5px]">Saldo Kas Tersedia</span>
                <span class="text-xl">👛</span>
            </div>
            <dd class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Rp {{ number_format($balance, 0, ',', '.') }}
            </dd>
            <p class="text-[11px] text-emerald-50 font-semibold">Dana siap digunakan</p>
        </div>

        {{-- Total Kas Masuk --}}
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Total Masuk</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-950 border border-emerald-200 flex items-center justify-center text-sm font-bold">↓</div>
            </div>
            <dd class="text-xl sm:text-2xl font-extrabold text-emerald-700 tracking-tight">
                +Rp {{ number_format($totalIn, 0, ',', '.') }}
            </dd>
            <p class="text-[11px] text-slate-500 truncate font-semibold">Akumulasi penerimaan</p>
        </div>

        {{-- Total Kas Keluar --}}
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Total Keluar</span>
                <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-800 border border-slate-200 flex items-center justify-center text-sm font-bold">↑</div>
            </div>
            <dd class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                -Rp {{ number_format($totalOut, 0, ',', '.') }}
            </dd>
            <p class="text-[11px] text-slate-500 truncate font-semibold">Pengeluaran kelas</p>
        </div>

        {{-- Total Transaksi --}}
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Total Transaksi</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-950 border border-emerald-200 flex items-center justify-center text-sm font-bold">🧾</div>
            </div>
            <dd class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                {{ $entries->total() }}
            </dd>
            <p class="text-[11px] text-slate-500 truncate font-semibold">Pencatatan kas aktif</p>
        </div>

    </dl>

    {{-- ══════════ 3. MAIN SECTION: LEDGER & FORM ══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT 2-COLS: TRANSACTION LEDGER --}}
        <section class="bg-white rounded-3xl border border-emerald-200/80 shadow-xs overflow-hidden lg:col-span-2">
            {{-- Header & Filter --}}
            <div class="p-4 sm:px-6 sm:py-4 border-b border-emerald-100 bg-emerald-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-lg">📜</span>
                    <h2 class="text-sm font-bold text-slate-900">Riwayat Mutasi Kas</h2>
                </div>

                {{-- Filter Pemasukan / Pengeluaran --}}
                <div class="inline-flex bg-emerald-100/70 p-1 rounded-2xl border border-emerald-200">
                    <a href="{{ route('classes.cashbook.index', $classroom) }}"
                       class="px-3.5 py-1 rounded-xl text-xs font-bold transition-all {{ !request('type') ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-700 hover:text-slate-900' }}">
                        Semua
                    </a>
                    <a href="{{ route('classes.cashbook.index', [$classroom, 'type' => 'in']) }}"
                       class="px-3.5 py-1 rounded-xl text-xs font-bold transition-all {{ request('type') === 'in' ? 'bg-white text-emerald-950 shadow-xs' : 'text-slate-700 hover:text-slate-900' }}">
                        Masuk
                    </a>
                    <a href="{{ route('classes.cashbook.index', [$classroom, 'type' => 'out']) }}"
                       class="px-3.5 py-1 rounded-xl text-xs font-bold transition-all {{ request('type') === 'out' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-700 hover:text-slate-900' }}">
                        Keluar
                    </a>
                </div>
            </div>

            @if ($entries->isNotEmpty())
                <div class="divide-y divide-emerald-100">
                    @foreach ($entries as $e)
                        <div class="p-4 sm:px-6 hover:bg-emerald-50/40 transition-colors flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-sm font-bold shrink-0 shadow-2xs border
                                            {{ $e->type === 'in' ? 'bg-emerald-100 text-emerald-950 border-emerald-200' : 'bg-slate-100 text-slate-900 border-slate-300' }}">
                                    {{ $e->type === 'in' ? '↓' : '↑' }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-slate-900 truncate">{{ $e->description }}</h3>
                                    <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5 font-medium">
                                        <span class="font-mono">{{ $e->transaction_date->format('d/m/Y') }}</span>
                                        @if ($e->student)
                                            <span class="text-slate-300">&middot;</span>
                                            <span class="font-bold text-slate-800 truncate">👤 {{ $e->student->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <div class="text-right">
                                    <p class="font-mono text-sm sm:text-base font-extrabold tracking-tight {{ $e->type === 'in' ? 'text-emerald-700' : 'text-slate-900' }}">
                                        {{ $e->type === 'in' ? '+' : '-' }}Rp {{ number_format($e->amount, 0, ',', '.') }}
                                    </p>
                                    <span class="text-[10px] uppercase font-bold text-slate-400">
                                        {{ $e->type === 'in' ? 'Pemasukan' : 'Pengeluaran' }}
                                    </span>
                                </div>

                                <form method="POST" action="{{ route('classes.cashbook.destroy', [$classroom, $e]) }}"
                                      onsubmit="return confirm('Hapus transaksi &quot;{{ $e->description }}&quot;?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-900 flex items-center justify-center transition-colors"
                                            title="Hapus Transaksi">
                                        ✕
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($entries->hasPages())
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-emerald-100 px-5 py-3.5 text-xs text-slate-500 bg-emerald-50/50">
                        <span class="font-medium text-slate-700">Menampilkan {{ $entries->firstItem() }}–{{ $entries->lastItem() }} dari {{ $entries->total() }} transaksi</span>
                        <div>{{ $entries->links() }}</div>
                    </div>
                @endif
            @else
                <div class="p-12 text-center space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-3xl mx-auto shadow-xs border border-emerald-200">💰</div>
                    <div class="space-y-1 max-w-xs mx-auto">
                        <p class="text-sm font-bold text-slate-900">Belum Ada Transaksi Kas</p>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Catat pemasukan iuran siswa atau pengeluaran kelas pertama Anda melalui formulir di samping.
                        </p>
                    </div>
                </div>
            @endif
        </section>

        {{-- RIGHT 1-COL: TRANSACTION INPUT FORM --}}
        <div class="space-y-4">
            {{-- WhatsApp Reminder Card --}}
            <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-2">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-950 border border-emerald-200 flex items-center justify-center text-lg font-bold shrink-0">💬</div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900">Pengingat Iuran WA</h3>
                        <p class="text-[11px] text-slate-500 font-medium">
                            {{ $classroom->spp_pengingat_aktif ? 'Status: Aktif' : 'Status: Nonaktif' }} &middot;
                            <a href="{{ route('whatsapp.index') }}" class="font-bold text-emerald-800 hover:underline">Kelola &rsaquo;</a>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Input Form Card --}}
            <div class="bg-white rounded-3xl border border-emerald-200/80 p-5 sm:p-6 shadow-xs space-y-4">
                <div class="flex items-center gap-2 border-b border-emerald-100 pb-3">
                    <span class="text-xl">✍️</span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Catat Transaksi Kas</h3>
                        <p class="text-[11px] text-slate-500">Input kas masuk / kas keluar</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('classes.cashbook.store', $classroom) }}" class="space-y-3.5"
                      x-data="{ loading: false, jenis: 'in' }" @submit="loading = true">
                    @csrf

                    {{-- Jenis Transaksi Segmented Control --}}
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-900">Jenis Transaksi:</label>
                        <div class="grid grid-cols-2 gap-2 bg-emerald-100/70 p-1 rounded-2xl border border-emerald-200">
                            <button type="button" @click="jenis = 'in'"
                                    :class="jenis === 'in' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'text-slate-700 font-semibold'"
                                    class="py-1.5 rounded-xl text-xs transition-all">
                                ↓ Kas Masuk
                            </button>
                            <button type="button" @click="jenis = 'out'"
                                    :class="jenis === 'out' ? 'bg-slate-800 text-white shadow-xs font-bold' : 'text-slate-700 font-semibold'"
                                    class="py-1.5 rounded-xl text-xs transition-all">
                                ↑ Kas Keluar
                            </button>
                        </div>
                        <input type="hidden" name="type" :value="jenis">
                    </div>

                    {{-- Jumlah Nominal --}}
                    <div class="space-y-1">
                        <label for="amount" class="block text-xs font-bold text-slate-900">Jumlah Nominal (Rp):</label>
                        <input type="number" id="amount" name="amount" value="{{ old('amount') }}" placeholder="Contoh: 10000" min="1" required
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-bold">
                    </div>

                    {{-- Tanggal --}}
                    <div class="space-y-1">
                        <label for="transaction_date" class="block text-xs font-bold text-slate-900">Tanggal Transaksi:</label>
                        <input type="date" id="transaction_date" name="transaction_date" value="{{ date('Y-m-d') }}" required
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>

                    {{-- Keterangan --}}
                    <div class="space-y-1">
                        <label for="description" class="block text-xs font-bold text-slate-900">Keterangan / Keperluan:</label>
                        <input type="text" id="description" name="description" value="{{ old('description') }}" placeholder="Contoh: Kas Mingguan, Beli Spidol..." required
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>

                    {{-- Siswa Pembayar --}}
                    <div class="space-y-1">
                        <label for="student_id" class="block text-xs font-bold text-slate-900">Siswa Pembayar (Opsional):</label>
                        <select id="student_id" name="student_id"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 bg-white font-medium text-slate-900">
                            <option value="">— Umum / Kas Seluruh Siswa —</option>
                            @foreach ($students as $st)
                                <option value="{{ $st->id }}" @selected(old('student_id') == $st->id)>{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" :disabled="loading"
                            class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-200 transition-all flex items-center justify-center gap-1.5 pt-2">
                        <template x-if="!loading">
                            <span>💾 Simpan Transaksi Kas</span>
                        </template>
                        <template x-if="loading">
                            <span>Menyimpan...</span>
                        </template>
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection
