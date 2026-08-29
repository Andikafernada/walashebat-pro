import './bootstrap';
import Alpine from 'alpinejs';

/*
 * Papan hitung absensi pada halaman roster publik.
 *
 * Didaftarkan lewat Alpine.data() dan BUKAN sebagai <script> di dalam Blade:
 * berkas Blade tidak melewati Vite, jadi apa pun yang butuh modul di sana
 * hanya akan gagal di konsol.
 *
 * Isian tiap baris tetap milik <input type="radio"> biasa. Alpine hanya
 * menambah hitungan dan tombol pintas di atasnya — bila JavaScript gagal
 * dimuat, formulirnya masih bisa diisi dan dikirim seperti formulir HTML biasa.
 */
Alpine.data('rosterAbsensi', (total = 0) => ({
    total,
    mengirim: false,
    jumlah: { hadir: 0, terlambat: 0, sakit: 0, izin: 0, alfa: 0 },
    sisa: total,

    init() {
        // Kiriman yang gagal validasi kembali dengan isian lama; hitung ulang
        // supaya papan angkanya cocok dengan apa yang terlihat di daftar.
        this.hitung();
    },

    hitung() {
        const jumlah = { hadir: 0, terlambat: 0, sakit: 0, izin: 0, alfa: 0 };

        this.$el.querySelectorAll('input[type="radio"]:checked').forEach((input) => {
            if (input.value in jumlah) {
                jumlah[input.value] += 1;
            }
        });

        this.jumlah = jumlah;

        const terisi = Object.values(jumlah).reduce((a, b) => a + b, 0);
        this.sisa = Math.max(0, this.total - terisi);
    },

    /*
     * Nilai disiarkan lewat event window, bukan dengan mengubah properti
     * .checked satu per satu. Mengubah .checked secara langsung tidak
     * memicu event apa pun, sehingga x-model tiap baris — yang mengatur
     * tampil/sembunyinya kolom catatan — akan tertinggal dari tampilan.
     */
    isiSemua(nilai) {
        window.dispatchEvent(new CustomEvent('roster-isi', { detail: nilai }));
        this.$nextTick(() => this.hitung());
    },

    tandaiSemuaHadir() {
        this.isiSemua('hadir');
    },

    kosongkan() {
        this.isiSemua(null);
    },
}));

/*
 * Sistem Toast Notification global.
 *
 * Pakai dari mana saja:
 *   window.dispatchEvent(new CustomEvent('toast', {
 *     detail: { message: 'Berhasil!', type: 'success' }
 *   }));
 *
 * type: 'success' | 'error' | 'info' | 'warning'
 * Durasi: 4000ms auto-dismiss
 */
Alpine.data('toastSystem', () => ({
    daftar: [],
    id: 0,

    tambah({ message, type = 'info' }) {
        const kunci = ++this.id;
        this.daftar.push({ kunci, message, type });

        // Hapus setelah 4 detik
        setTimeout(() => this.hapus(kunci), 4000);
    },

    hapus(kunci) {
        const el = this.$refs.container?.querySelector(`[data-kunci="${kunci}"]`);
        if (el) {
            el.classList.add('hilang');
            setTimeout(() => {
                this.daftar = this.daftar.filter((t) => t.kunci !== kunci);
            }, 200);
        } else {
            this.daftar = this.daftar.filter((t) => t.kunci !== kunci);
        }
    },
}));

/*
 * SAFETY NET — global error handlers.
 *
 * catch() lokal di setiap fetch sudah menangani sebagian besar error,
 * tetapi ini menangkap apa pun yang lolos: rejection dari async yang tidak
 * di-await, error dari Alpine itu sendiri, atau exception di event listener.
 *
 * Hanya mencatat — tidak pernah melempar ulang atau merusak UI.
 */
window.addEventListener('error', (e) => {
    // Abaikan error dari ekstensi browser dan cross-origin resource error
    if (e.filename && !e.filename.includes(window.location.origin)) return;
    console.error('[Unhandled Error]', e.message, 'at', e.filename + ':' + e.lineno);
});

window.addEventListener('unhandledrejection', (e) => {
    // Promise rejection tanpa .catch(). Biasanya async function yang tidak
    // di-await, bukan error jaringan — tapi catat agar tahu sumbernya.
    const msg = e.reason?.message || e.reason?.toString() || String(e.reason);
    if (msg && msg !== '[object Promise]') {
        console.error('[Unhandled Promise Rejection]', msg);
    }
});

// ── Alpine Global Error Handler ──────────────────────────────────────────────
document.addEventListener('alpine:init', () => {
    // Alpine menangkap error di dalam x-data dan menanganinya sendiri.
    // Handler ini menangkap apa pun yang SUPER GLOBAL — saat Alpine
    // belum atau sudah tidak berjalan, atau error yang berasal dari
    // luar scope Alpine (mis. alpine:init listener yang throw).
    Alpine.exceptionHandler = (error, element, AlpineComponent) => {
        console.error('[Alpine Error]', error.message, element);
    };
});

window.Alpine = Alpine;
Alpine.start();
