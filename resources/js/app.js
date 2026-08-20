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

window.Alpine = Alpine;
Alpine.start();
