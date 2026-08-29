@extends('layouts.app-operator')

@section('title', 'Guru: '.$guru->name)

@section('content')
@php
    $aktif = $guru->subscription_ends_at && $guru->subscription_ends_at->isFuture();
    $pro = $guru->subscription_tier === \App\Models\User::TIER_PRO;
    $tersambung = $guru->wa_session_status === 'connected';
@endphp

<div class="space-y-6 pb-12">

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('admin.teachers.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800">‹ Daftar Guru</a>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                {{ $guru->name }}
                @unless ($guru->is_active)
                    <span class="align-middle rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-bold text-slate-600">nonaktif</span>
                @endunless
            </h1>
            <p class="mt-1 text-xs text-slate-500">
                {{ $guru->school_name ?: 'Sekolah belum diisi' }}
                @if ($guru->school_npsn) &middot; NPSN {{ $guru->school_npsn }} @endif
            </p>
        </div>
        <div class="text-xs text-slate-500 sm:text-right shrink-0 space-y-2">
            <div>
                <p class="font-semibold">Daftar {{ $guru->created_at->translatedFormat('d F Y') }}</p>
                <p class="text-slate-400">{{ $guru->created_at->diffForHumans() }}</p>
            </div>
            <div class="flex flex-wrap sm:justify-end gap-2">
                <form method="POST" action="{{ route('admin.teachers.toggle-active', $guru) }}"
                      @if ($guru->is_active) onsubmit="return confirm('Nonaktifkan akun {{ $guru->name }}? Ia langsung ter-logout dan tidak bisa masuk sampai diaktifkan lagi.')" @endif>
                    @csrf
                    @if ($guru->is_active)
                        <button type="submit" class="rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-700 transition-colors">Nonaktifkan Akun</button>
                    @else
                        <button type="submit" class="rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">Aktifkan Kembali</button>
                    @endif
                </form>
                <form method="POST" action="{{ route('admin.teachers.reset-password', $guru) }}"
                      onsubmit="return confirm('Reset kata sandi {{ $guru->name }}? Sandi lama langsung tidak berlaku dan sandi sementara akan muncul sekali.')">
                    @csrf
                    <button type="submit" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Reset Sandi</button>
                </form>
                @if ($guru->is_active)
                    <form method="POST" action="{{ route('admin.teachers.impersonate', $guru) }}"
                          onsubmit="return confirm('Masuk sebagai {{ $guru->name }}? Anda akan melihat aplikasi persis seperti guru ini sampai menekan Kembali ke admin.')">
                        @csrf
                        <button type="submit" class="rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition-colors">Masuk Sebagai Guru</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @include('partials.flash')

    <!-- Tiga hal yang selalu ditanyakan lebih dulu -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-5 space-y-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Langganan</p>
                <p class="mt-1 text-lg font-extrabold {{ $pro ? 'text-amber-800' : 'text-slate-900' }}">{{ $pro ? 'PRO' : 'Gratis' }}</p>
                <p class="mt-0.5 text-[11px] font-semibold {{ $aktif ? 'text-emerald-700' : 'text-rose-600' }}">
                    {{ $guru->subscription_ends_at
                        ? ($aktif ? 'aktif sampai '.$guru->subscription_ends_at->translatedFormat('d M Y') : 'habis '.$guru->subscription_ends_at->translatedFormat('d M Y'))
                        : 'belum pernah berjalan' }}
                </p>
            </div>

            <form method="POST" action="{{ route('admin.teachers.grant-pro', $guru) }}"
                  onsubmit="return confirm('Beri akses PRO ke {{ $guru->name }}?')"
                  class="flex items-center gap-2 pt-2 border-t border-slate-100">
                @csrf
                <input type="number" name="bulan" min="1" max="24" value="1" required
                       class="w-16 rounded-xl border border-slate-200 bg-white text-xs py-1.5 px-2 font-mono text-slate-900 focus:border-emerald-500 focus:outline-none">
                <span class="text-[11px] text-slate-500">bln</span>
                <button type="submit"
                        class="rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors shadow-xs">Beri PRO</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Gateway WhatsApp</p>
            <p class="mt-1 text-lg font-extrabold {{ $tersambung ? 'text-emerald-700' : 'text-rose-700' }}">
                {{ $tersambung ? 'Tersambung' : ($guru->wa_session_status ?: 'belum pernah') }}
            </p>
            <p class="mt-0.5 text-[11px] text-slate-500">
                {{ $guru->wa_connected_at ? 'terakhir '.$guru->wa_connected_at->diffForHumans() : 'belum pernah tersambung' }}
            </p>
            @if ($guru->wa_last_error)
                <p class="mt-1 text-[11px] font-semibold text-rose-600 break-words">{{ Str::limit($guru->wa_last_error, 90) }}</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Kontak</p>
            <p class="mt-1 text-sm font-bold text-slate-900 break-all font-mono">{{ $guru->whatsapp_number ?: 'nomor belum diisi' }}</p>
            <p class="mt-0.5 text-[11px] text-slate-500 break-all font-mono">{{ $guru->email }}</p>
        </div>
    </div>

    <!-- Kelas -->
    <div class="rounded-2xl border border-emerald-200 bg-white shadow-xs overflow-hidden">
        <div class="flex items-baseline justify-between gap-3 border-b border-emerald-100 px-5 py-3.5">
            <h2 class="text-sm font-extrabold text-slate-900">Kelas</h2>
            <span class="text-xs font-bold text-slate-400">{{ $kelas->count() }} kelas</span>
        </div>
        @if ($kelas->isEmpty())
            <p class="px-5 py-6 text-center text-xs font-semibold text-slate-500">Guru ini belum membuat kelas satu pun.</p>
        @else
            <table class="w-full text-left text-xs">
                <tbody class="divide-y divide-slate-100">
                    @foreach ($kelas as $k)
                        <tr class="{{ $k->is_active ? '' : 'bg-slate-50' }} hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3 font-bold text-slate-800">
                                {{ $k->name }}
                                @unless ($k->is_active)
                                    <span class="ml-1 rounded-full bg-slate-200 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">diarsipkan</span>
                                @endunless
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $k->jenis === \App\Models\Classroom::JENIS_AJAR ? 'Kelas ajar' : 'Perwalian' }}</td>
                            <td class="px-5 py-3 text-right font-bold text-slate-700 font-mono">{{ $k->students_count }} siswa</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!-- Sesi Absensi Terakhir -->
    <div class="rounded-2xl border border-emerald-200 bg-white shadow-xs overflow-hidden">
        <div class="flex items-baseline justify-between gap-3 border-b border-emerald-100 px-5 py-3.5">
            <h2 class="text-sm font-extrabold text-slate-900">Sesi Absensi Terakhir</h2>
            <span class="text-xs font-bold text-slate-400">10 terbaru</span>
        </div>
        @if ($sesiTerakhir->isEmpty())
            <p class="px-5 py-6 text-center text-xs font-semibold text-slate-500">Belum pernah menerbitkan sesi absensi.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50/60 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-5 py-2.5">Tanggal</th>
                            <th class="px-5 py-2.5">Kelas</th>
                            <th class="px-5 py-2.5">Sesi</th>
                            <th class="px-5 py-2.5">Pengiriman</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($sesiTerakhir as $s)
                            <tr class="{{ $s->delivery_status === 'failed' ? 'bg-rose-50/50' : '' }} hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-2.5 whitespace-nowrap text-slate-700 font-mono">{{ $s->session_date->translatedFormat('d M Y') }}</td>
                                <td class="px-5 py-2.5 text-slate-600 font-semibold">{{ $s->classroom?->name ?? '—' }}</td>
                                <td class="px-5 py-2.5">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $s->status === 'submitted' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $s->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-2.5">
                                    @if ($s->delivery_status === 'failed')
                                        <span class="font-bold text-rose-700">gagal</span>
                                        <span class="block text-[11px] text-rose-600">{{ Str::limit($s->delivery_error, 100) ?: 'tanpa keterangan' }}</span>
                                    @elseif ($s->delivered_at)
                                        <span class="text-emerald-700 font-bold">terkirim {{ $s->delivered_at->format('H:i') }}</span>
                                        <span class="block text-[11px] text-slate-400 font-mono">{{ $s->delivery_target }}</span>
                                    @else
                                        <span class="text-slate-400">{{ $s->delivery_status ?: 'tidak dikirim' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Pembayaran -->
    <div class="rounded-2xl border border-emerald-200 bg-white shadow-xs overflow-hidden">
        <div class="flex items-baseline justify-between gap-3 border-b border-emerald-100 px-5 py-3.5">
            <h2 class="text-sm font-extrabold text-slate-900">Riwayat Pembayaran</h2>
            <a href="{{ route('admin.subscriptions.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800">Kelola di halaman langganan ›</a>
        </div>
        @if ($pembayaran->isEmpty())
            <p class="px-5 py-6 text-center text-xs font-semibold text-slate-500">Belum pernah mengirim bukti pembayaran.</p>
        @else
            <table class="w-full text-left text-xs">
                <tbody class="divide-y divide-slate-100">
                    @foreach ($pembayaran as $p)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3 whitespace-nowrap text-slate-700 font-mono">{{ $p->created_at->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-3 text-slate-600 font-bold">Rp {{ number_format((int) $p->amount, 0, ',', '.') }}
                                @if ($p->granted_months)
                                    <span class="text-slate-400 font-normal">&middot; {{ $p->granted_months }} bulan</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $p->sender_name ?: '—' }}</td>
                            <td class="px-5 py-3">
                                @php
                                    $gaya = match ($p->status) {
                                        'approved' => 'bg-emerald-100 text-emerald-800',
                                        'rejected' => 'bg-rose-100 text-rose-800',
                                        default => 'bg-amber-100 text-amber-800',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $gaya }}">{{ $p->status }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>
@endsection
