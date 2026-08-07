<div class="sm:col-span-2"
     x-data="pemilihGrup(
        '{{ route('whatsapp.groups') }}',
        '{{ route('whatsapp.groups.test') }}',
        '{{ old('parent_group_wa', $classroom->parent_group_wa ?? '') }}'
     )"
     x-init="muat()">

    <label class="form-label" for="parent_group_wa">
        Grup WhatsApp Orang Tua <span class="font-normal text-slate-400">(opsional)</span>
    </label>

    <div x-show="memuat" class="mt-1 text-sm text-slate-500 flex items-center gap-2">
        <span class="spinner spinner--indigo"></span> Mengambil daftar grup…
    </div>

    <template x-if="! memuat && ! tersambung">
        <div class="mt-1 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-800 flex items-center justify-between">
            <div>
                <span class="font-bold block text-amber-900">Perangkat WhatsApp Belum Tersambung</span>
                Tautkan nomor WhatsApp Anda di menu Perangkat WA untuk memilih grup.
            </div>
            <a href="{{ route('whatsapp.index') }}" class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-amber-700">Tautkan WA</a>
        </div>
    </template>

    <template x-if="! memuat && tersambung && grup.length === 0">
        <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs text-slate-600">
            Tidak ada grup ditemukan pada akun WhatsApp Anda. Pastikan nomor Anda tergabung di grup kelas.
        </div>
    </template>

    <!-- LOCKED STATE DISPLAY -->
    <div x-show="! memuat && tersambung && locked && terpilih" x-cloak class="mt-1">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-indigo-200 bg-gradient-to-r from-indigo-50/80 to-purple-50/50 p-3.5 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-xs">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-indigo-900" x-text="namaGrupTerpilih()"></span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
                            🔒 Terkunci
                        </span>
                    </div>
                    <p class="text-[11px] text-indigo-600/80">Pesan rekap absensi otomatis dikirim ke grup ini.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button type="button" @click="uji()" :disabled="menguji"
                        class="rounded-lg border border-indigo-300 bg-white px-3 py-1.5 text-xs font-bold text-indigo-700 shadow-xs hover:bg-indigo-50 disabled:opacity-50">
                    <span x-text="menguji ? 'Mengirim…' : 'Kirim Pesan Uji'"></span>
                </button>
                <button type="button" @click="locked = false"
                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition-colors">
                    Ubah Pilihan
                </button>
            </div>
        </div>
        <input type="hidden" name="parent_group_wa" :value="terpilih">
        <span x-show="hasil" x-text="hasil"
              :class="berhasil ? 'text-xs font-semibold text-emerald-600 mt-1 block' : 'text-xs font-semibold text-rose-600 mt-1 block'"></span>
    </div>

    <!-- UNLOCKED / EDIT SELECT DISPLAY -->
    <div x-show="! memuat && tersambung && (! locked || ! terpilih)" x-cloak class="mt-1 space-y-2">
        <div class="flex items-center gap-2">
            <select id="parent_group_wa" name="parent_group_wa"
                    x-model="terpilih"
                    class="w-full rounded-xl border-slate-200 text-xs focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                <option value="">— Tidak Mengirim Rekap ke Grup WhatsApp —</option>
                <template x-for="g in grup" :key="g.id">
                    <option :value="g.id" x-text="g.subject + ' (' + g.peserta + ' Anggota)'"></option>
                </template>
            </select>
            <template x-if="terpilih">
                <button type="button" @click="locked = true" class="shrink-0 rounded-xl bg-slate-800 px-3 py-2 text-xs font-bold text-white shadow-xs hover:bg-slate-900">
                    Kunci Pilihan
                </button>
            </template>
        </div>

        <div x-show="terpilih" class="flex flex-wrap items-center gap-3">
            <button type="button" @click="uji()" :disabled="menguji"
                    class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 disabled:opacity-50">
                <span x-text="menguji ? 'Mengirim…' : 'Kirim Pesan Uji'"></span>
            </button>
            <span x-show="hasil" x-text="hasil"
                  :class="berhasil ? 'text-xs text-emerald-600 font-semibold' : 'text-xs text-rose-600 font-semibold'"></span>
        </div>
    </div>

    <p class="mt-2 text-xs text-slate-400">
        Setelah petugas mengirim absensi, pesan rekap otomatis dikirim ke grup ini. 
        Tekan <b>Kirim Pesan Uji</b> untuk memastikan grup sebelum disimpan.
    </p>

    @error('parent_group_wa')
        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
    @enderror
</div>

<script>
    function pemilihGrup(url, urlUji, awal) {
        return {
            memuat: true,
            tersambung: false,
            grup: [],
            terpilih: awal || '',
            locked: Boolean(awal),
            menguji: false,
            hasil: '',
            berhasil: false,

            namaGrupTerpilih() {
                const item = this.grup.find(g => g.id === this.terpilih);
                return item ? item.subject + ' (' + item.peserta + ' Anggota)' : this.terpilih;
            },

            async muat() {
                try {
                    const res = await fetch(url, { headers: { Accept: 'application/json' } });
                    const data = await res.json();
                    this.tersambung = data.connected;
                    this.grup = data.groups || [];
                } catch (e) {
                    this.tersambung = false;
                }
                this.memuat = false;
            },

            async uji() {
                if (! this.terpilih) return;
                this.menguji = true;
                this.hasil = '';
                try {
                    const res = await fetch(urlUji, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ group_id: this.terpilih }),
                    });
                    const data = await res.json();
                    this.berhasil = data.ok === true;
                    this.hasil = data.pesan || (this.berhasil ? 'Terkirim.' : 'Gagal.');
                } catch (e) {
                    this.berhasil = false;
                    this.hasil = 'Tidak dapat menghubungi server.';
                }
                this.menguji = false;
                setTimeout(() => { this.hasil = ''; }, 8000);
            },
        };
    }
</script>
