@extends('layouts.app')
@section('title', 'Pengaturan Akun')
@section('content')

    <div class="max-w-2xl space-y-5 pb-12">

        <!-- User Avatar + Info -->
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-5">
            <div class="flex items-center gap-5">
                <!-- Avatar -->
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-xl font-extrabold text-emerald-800 border border-emerald-200">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-lg font-bold text-slate-900 truncate">{{ $user->name }}</h2>
                    <p class="text-xs text-slate-500 truncate">{{ $user->email }}</p>
                    @if($user->whatsapp_number)
                        <p class="text-xs text-slate-400 mt-0.5 font-mono">{{ $user->whatsapp_number }}</p>
                    @endif
                </div>
                <div class="shrink-0">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">Guru PRO</span>
                </div>
            </div>
        </div>

        <!-- School Info Card -->
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-5 space-y-4">
            <div class="flex items-center gap-3 border-b border-emerald-100 pb-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Identitas Sekolah &amp; Profil</h3>
                    <p class="text-xs text-slate-500">Data ini dipakai sebagai kop dan blok tanda tangan pada laporan cetak.</p>
                </div>
            </div>

            <form method="POST"
                  action="{{ route('profile.update') }}"
                  class="space-y-4"
                  x-data="{ loading: false }"
                  @submit="loading = true">

                @method('PATCH')
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" autocomplete="name">
                        @error('name')
                            <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email <span class="text-rose-500">*</span></label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" autocomplete="email">
                        @error('email')
                            <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="whatsapp_number" class="block text-xs font-semibold text-slate-700 mb-1">No. WhatsApp</label>
                    <input type="tel" id="whatsapp_number" name="whatsapp_number"
                           value="{{ old('whatsapp_number', $user->whatsapp_number) }}"
                           placeholder="6281234567890"
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <p class="mt-1 text-xs text-slate-400">Nomor pengirim magic link. Boleh ditulis 08... — otomatis diubah ke 628...</p>
                    @error('whatsapp_number')
                        <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="border-t border-slate-100 pt-4">
                    <div>
                        <label for="school_name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Sekolah <span class="text-rose-500">*</span></label>
                        <input type="text" id="school_name" name="school_name"
                               value="{{ old('school_name', $user->school_name) }}"
                               required placeholder="SMK Pasundan 2 Bandung"
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" autocomplete="organization">
                        @error('school_name')
                            <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="school_address" class="block text-xs font-semibold text-slate-700 mb-1">Alamat Sekolah</label>
                    <input type="text" id="school_address" name="school_address"
                           value="{{ old('school_address', $user->school_address) }}"
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" autocomplete="street-address">
                    @error('school_address')
                        <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="school_city" class="block text-xs font-semibold text-slate-700 mb-1">Kota / Kabupaten</label>
                        <input type="text" id="school_city" name="school_city"
                               value="{{ old('school_city', $user->school_city) }}"
                               placeholder="Bandung" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <p class="mt-1 text-xs text-slate-400">Dipakai untuk baris tanggal di atas tanda tangan.</p>
                        @error('school_city')
                            <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="school_npsn" class="block text-xs font-semibold text-slate-700 mb-1">NPSN</label>
                        <input type="text" id="school_npsn" name="school_npsn"
                               value="{{ old('school_npsn', $user->school_npsn) }}"
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" autocomplete="off">
                        @error('school_npsn')
                            <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nip" class="block text-xs font-semibold text-slate-700 mb-1">NIP Anda</label>
                        <input type="text" id="nip" name="nip"
                               value="{{ old('nip', $user->nip) }}"
                               placeholder="19850712 201001 1 004"
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <p class="mt-1 text-xs text-slate-400">Muncul di bawah tanda tangan Anda.</p>
                        @error('nip')
                            <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="principal_name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Kepala Sekolah</label>
                        <input type="text" id="principal_name" name="principal_name"
                               value="{{ old('principal_name', $user->principal_name) }}"
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" autocomplete="off">
                        @error('principal_name')
                            <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="principal_nip" class="block text-xs font-semibold text-slate-700 mb-1">NIP Kepala Sekolah</label>
                    <input type="text" id="principal_nip" name="principal_nip"
                           value="{{ old('principal_nip', $user->principal_nip) }}"
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" autocomplete="off">
                    @error('principal_nip')
                        <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors disabled:opacity-50"
                        :disabled="loading">
                    <span x-show="!loading">Simpan Perubahan</span>
                    <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                        <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        Menyimpan...
                    </span>
                </button>
            </form>
        </div>

        <!-- Security Settings -->
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-5 space-y-4">
            <div class="flex items-center gap-3 border-b border-emerald-100 pb-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Ubah Kata Sandi</h3>
                    <p class="text-xs text-slate-500">Perbarui kata sandi akun Anda untuk menjaga keamanan.</p>
                </div>
            </div>

            <form method="POST"
                  action="{{ route('profile.password') }}"
                  class="space-y-4"
                  x-data="{ loading: false }"
                  @submit="loading = true">

                @method('PATCH')
                @csrf

                <div>
                    <label for="current_password" class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Saat Ini <span class="text-rose-500">*</span></label>
                    <input type="password" id="current_password" name="current_password" required
                           autocomplete="current-password"
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @error('current_password')
                        <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Baru <span class="text-rose-500">*</span></label>
                        <input type="password" id="password" name="password" required
                               autocomplete="new-password"
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('password')
                            <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               autocomplete="new-password"
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors disabled:opacity-50"
                        :disabled="loading">
                    <span x-show="!loading">Ubah Kata Sandi</span>
                    <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                        <span class="h-4 w-4 border-2 border-slate-600 border-t-transparent rounded-full animate-spin"></span>
                        Menyimpan...
                    </span>
                </button>
            </form>
        </div>

    
        {{-- Sesi & Keluar Akun --}}
        <div class="bg-white rounded-2xl border border-rose-200 shadow-xs p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-1.5">
                    <span>🚪</span>
                    <span>Keluar dari Aplikasi</span>
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Akhiri sesi login akun Anda di perangkat ini.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Keluar dari akun Anda?');" class="shrink-0">
                @csrf
                <button type="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white text-xs font-bold shadow-xs transition-colors">
                    <span>Keluar / Logout</span>
                    <span>&rarr;</span>
                </button>
            </form>
        </div>
    </div>
@endsection