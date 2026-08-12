@extends('layouts.app')

@section('title', 'Persetujuan Pembayaran PRO (Admin)')

@section('content')
<div class="space-y-6 pb-12">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
            Panel Admin: Persetujuan Pembayaran PRO
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">Konfirmasi foto bukti transfer dari guru untuk mengaktifkan paket PRO.</p>
    </div>

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- PENDING APPROVALS TABLE -->
    <div class="card space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Menunggu Konfirmasi ({{ $pending->count() }} Pengajuan)</h3>
                <p class="text-xs text-slate-500">Periksa bukti transfer di bawah lalu klik Setujui</p>
            </div>
            <span class="rounded-sm bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold text-amber-800">
                Pending Approval
            </span>
        </div>

        @if($pending->isNotEmpty())
            <div class="overflow-x-auto rounded border border-slate-200">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <th scope="col" class="px-3 py-2">Guru &amp; Sekolah</th>
                            <th scope="col" class="px-3 py-2">Paket</th>
                            <th scope="col" class="px-3 py-2">Pengirim / Bank</th>
                            <th scope="col" class="px-3 py-2 text-center">Bukti Transfer</th>
                            <th scope="col" class="px-3 py-2 text-center">Aksi Konfirmasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($pending as $p)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-3 py-2">
                                    <span class="font-semibold text-slate-900 block">{{ $p->user->name ?? 'User #'.$p->user_id }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ $p->user->email }} &middot; WA: {{ $p->user->whatsapp_number ?: '-' }}</span>
                                    <span class="text-[10px] text-indigo-600 font-semibold block">{{ $p->user->school_name ?: 'Sekolah -' }}</span>
                                </td>
                                <td class="px-3 py-2 font-semibold text-slate-800 uppercase">
                                    {{--
                                        Nominalnya DIBACA dari baris, bukan diketik ulang
                                        di sini. Label lama menyebut "19rb" sementara guru
                                        disuruh transfer Rp 10.000 — dan yang membandingkan
                                        angka pada bukti transfer dengan angka di layar ini
                                        adalah operator yang memutuskan terima atau tolak.
                                        Label yang mengulang harga adalah sumber kebenaran
                                        kedua yang cepat atau lambat menyimpang.
                                    --}}
                                    <span class="inline-block rounded-md px-2 py-0.5 text-[10px] font-semibold {{ $p->plan_type === 'yearly' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800' }}">
                                        {{ $p->plan_type === 'yearly' ? 'PRO TAHUNAN' : 'PRO BULANAN' }}
                                        (Rp {{ number_format((int) $p->amount, 0, ',', '.') }})
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="font-semibold text-slate-800 block">{{ $p->sender_name }}</span>
                                    <span class="text-[10px] text-slate-500 block">{{ $p->bank_name ?: 'Bank / E-Wallet' }}</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <a href="{{ route('subscription.proof', $p) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline">
 Lihat Foto Bukti
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        {{--
                                            Jumlah bulan bisa disunting sebelum menyetujui, karena
                                            nominal transfer DANA yang masuk tidak selalu sama dengan
                                            paket yang dipilih guru di formulir. Nilainya sudah
                                            terisi sesuai paket, jadi persetujuan biasa tetap satu klik.
                                        --}}
                                        <form method="POST" action="{{ route('admin.subscriptions.approve', $p) }}" class="flex items-center gap-1.5">
                                            @csrf
                                            <label class="sr-only" for="bulan-{{ $p->id }}">Jumlah bulan</label>
                                            <input id="bulan-{{ $p->id }}" type="number" name="bulan" min="1" max="24"
                                                   value="{{ $p->plan_type === 'yearly' ? 12 : 1 }}"
                                                   class="h-8 w-14 rounded-lg border border-slate-300 text-center text-xs font-semibold text-slate-800 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                                   title="Berapa bulan yang diberikan">
                                            <span class="text-[10px] font-semibold text-slate-500">bln</span>
                                            <button type="submit" onclick="return confirm('Setujui pembayaran & aktifkan akun PRO?')" class="h-8 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-3 font-semibold text-xs transition-colors">
                                                ✓ Setujui PRO
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.subscriptions.reject', $p) }}">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Tolak bukti pembayaran ini?')" class="h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 px-2.5 font-semibold text-xs transition-colors">
                                                ✕ Tolak
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-8 text-center text-xs text-slate-400">
                Tidak ada pembayaran PRO yang menunggu konfirmasi saat ini.
            </div>
        @endif
    </div>

    <!-- HISTORY TABLE -->
    <div class="card space-y-4">
        <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-3">Riwayat Transaksi</h3>
        @if($history->isNotEmpty())
            <div class="overflow-x-auto rounded border border-slate-200">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <th scope="col" class="px-3 py-2">Tanggal</th>
                            <th scope="col" class="px-3 py-2">Guru</th>
                            <th scope="col" class="px-3 py-2">Paket</th>
                            <th scope="col" class="px-3 py-2 text-center">Status</th>
                            <th scope="col" class="px-3 py-2">Diproses Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($history as $h)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 text-slate-500">{{ $h->created_at->format('d M Y H:i') }}</td>
                                <td class="px-3 py-2 font-semibold text-slate-900">{{ $h->user->name ?? '-' }}</td>
                                <td class="px-3 py-2 text-slate-700 font-semibold">
                                    <span class="uppercase">{{ $h->plan_type }}</span>
                                    {{-- Bulan yang benar-benar diberikan; kosong untuk baris
                                         yang disetujui sebelum kolom ini ada. --}}
                                    @if ($h->granted_months)
                                        <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">
                                            {{ $h->granted_months }} bln
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-block rounded-md px-2 py-0.5 text-[10px] font-semibold {{ $h->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ ucfirst($h->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-slate-500">{{ $h->approver->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pt-2">{{ $history->links() }}</div>
        @else
            <div class="py-6 text-center text-xs text-slate-400">Belum ada riwayat transaksi.</div>
        @endif
    </div>
</div>
@endsection
