@extends('layouts.app')
@section('title', 'Pengaturan Akun')
@section('content')

    <div class="bento-grid max-w-2xl">

        <!-- User Avatar + Info → bento-wide bento-highlight -->
        <div class="bento-item bento-wide bento-highlight bento-highlight--sky">
            <div class="flex items-center gap-5">
                <!-- Avatar -->
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/80 text-2xl font-bold text-slate-600 shadow-sm">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-slate-900">{{ $user->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $user->email }}</p>
                    @if($user->whatsapp_number)
                        <p class="text-xs text-slate-400 mt-1">{{ $user->whatsapp_number }}</p>
                    @endif
                </div>
                <div class="shrink-0">
                    <span class="badge badge--sky">Akun</span>
                </div>
            </div>
        </div>

        <!-- School Info Card → bento-medium -->
        <div class="bento-item bento-medium stagger-1">
            <div class="card-elevated">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bento-icon bento-icon--sm bento-icon--sky">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-slate-900">Identitas Sekolah</h3>
                </div>
                <p class="text-xs text-slate-500">
                    Data ini dipakai sebagai kop dan blok tanda tangan pada laporan cetak.
                </p>

                <form method="POST"
                      action="{{ route('profile.update') }}"
                      class="space-y-4 mt-4"
                      x-data="{ loading: false }"
                      @submit="loading = true">

                    @method('PATCH')
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="form-group">
                            <label for="name" class="form-label form-label--required">Nama lengkap</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                                   class="form-input form-input--sm" autocomplete="name">
                            @error('name')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label form-label--required">Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="form-input form-input--sm" autocomplete="email">
                            @error('email')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="whatsapp_number" class="form-label">No. WhatsApp</label>
                        <input type="tel" id="whatsapp_number" name="whatsapp_number"
                               value="{{ old('whatsapp_number', $user->whatsapp_number) }}"
                               placeholder="6281234567890"
                               class="form-input form-input--sm">
                        <p class="form-hint">Nomor pengirim magic link. Boleh ditulis 08... — otomatis diubah ke 628...</p>
                        @error('whatsapp_number')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="border-t border-slate-100 pt-4">
                        <div class="form-group">
                            <label for="school_name" class="form-label form-label--required">Nama sekolah</label>
                            <input type="text" id="school_name" name="school_name"
                                   value="{{ old('school_name', $user->school_name) }}"
                                   required placeholder="SMK Pasundan 2 Bandung"
                                   class="form-input form-input--sm" autocomplete="organization">
                            @error('school_name')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="school_address" class="form-label">Alamat sekolah</label>
                        <input type="text" id="school_address" name="school_address"
                               value="{{ old('school_address', $user->school_address) }}"
                               class="form-input form-input--sm" autocomplete="street-address">
                        @error('school_address')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="form-group">
                            <label for="school_city" class="form-label">Kota / kabupaten</label>
                            <input type="text" id="school_city" name="school_city"
                                   value="{{ old('school_city', $user->school_city) }}"
                                   placeholder="Bandung" class="form-input form-input--sm">
                            <p class="form-hint">Dipakai untuk baris tanggal di atas tanda tangan.</p>
                            @error('school_city')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="school_npsn" class="form-label">NPSN</label>
                            <input type="text" id="school_npsn" name="school_npsn"
                                   value="{{ old('school_npsn', $user->school_npsn) }}"
                                   class="form-input form-input--sm" autocomplete="off">
                            @error('school_npsn')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="form-group">
                            <label for="nip" class="form-label">NIP Anda</label>
                            <input type="text" id="nip" name="nip"
                                   value="{{ old('nip', $user->nip) }}"
                                   placeholder="19850712 201001 1 004"
                                   class="form-input form-input--sm">
                            <p class="form-hint">Muncul di bawah tanda tangan Anda.</p>
                            @error('nip')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="principal_name" class="form-label">Nama kepala sekolah</label>
                            <input type="text" id="principal_name" name="principal_name"
                                   value="{{ old('principal_name', $user->principal_name) }}"
                                   class="form-input form-input--sm" autocomplete="off">
                            @error('principal_name')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="principal_nip" class="form-label">NIP kepala sekolah</label>
                        <input type="text" id="principal_nip" name="principal_nip"
                               value="{{ old('principal_nip', $user->principal_nip) }}"
                               class="form-input form-input--sm" autocomplete="off">
                        @error('principal_nip')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="btn-primary w-full justify-center"
                            :disabled="loading"
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
                        <span x-show="!loading" class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Simpan perubahan
                        </span>
                        <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                            <span class="spinner spinner--white"></span>
                            Menyimpan...
                        </span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Security Settings → bento-item card -->
        <div class="bento-item stagger-2">
            <div class="card-elevated">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bento-icon bento-icon--sm bento-icon--amber">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-slate-900">Ubah Kata Sandi</h3>
                </div>
                <p class="text-xs text-slate-500">
                    Perbarui kata sandi akun Anda untuk menjaga keamanan.
                </p>

                <form method="POST"
                      action="{{ route('profile.password') }}"
                      class="space-y-4 mt-4"
                      x-data="{ loading: false }"
                      @submit="loading = true">

                    @method('PATCH')
                    @csrf

                    <div class="form-group">
                        <label for="current_password" class="form-label form-label--required">Kata sandi saat ini</label>
                        <input type="password" id="current_password" name="current_password" required
                               autocomplete="current-password"
                               class="form-input form-input--sm">
                        @error('current_password')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="form-group">
                            <label for="password" class="form-label form-label--required">Kata sandi baru</label>
                            <input type="password" id="password" name="password" required
                                   autocomplete="new-password"
                                   class="form-input form-input--sm">
                            @error('password')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation" class="form-label form-label--required">Konfirmasi</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required
                                   autocomplete="new-password"
                                   class="form-input form-input--sm">
                        </div>
                    </div>

                    <button type="submit"
                            class="btn-secondary w-full justify-center"
                            :disabled="loading"
                            :class="loading ? 'opacity-50 cursor-not-allowed' : ''">
                        <span x-show="!loading" class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            Ubah kata sandi
                        </span>
                        <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                            <span class="spinner"></span>
                            Menyimpan...
                        </span>
                    </button>
                </form>
            </div>
        </div>

    </div>
@endsection
