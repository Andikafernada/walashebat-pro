import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/*
 * BUKU ADMINISTRASI KELAS
 *
 * Arahnya tenang dan rapat: kertas hangat, garis rambut 1px, tanpa bayangan,
 * sudut kecil, angka rata kolom. Yang dituju bukan dasbor SaaS melainkan buku
 * administrasi yang dipegang wali kelas — dan kerangka aplikasi ini memang
 * sudah memakai metaforanya (lihat "pembatas" di layouts/app.blade.php).
 *
 * NAMA WARNANYA SENGAJA TIDAK DIGANTI.
 *
 * Ada ~4.000 pemakaian kelas warna harfiah tersebar di 90 berkas Blade.
 * Menambah kosakata semantik baru (surface/ink/line) di samping yang lama
 * berarti dua sistem hidup berdampingan selama penyisiran, dan yang seperti itu
 * tidak pernah benar-benar selesai. Jadi yang diganti NILAINYA, bukan namanya:
 *
 *   slate  → netral hangat (kertas, garis, tinta teks)
 *   indigo → tinta biru-hitam, satu-satunya aksen
 *   emerald→ hijau papan tulis   (hadir, saldo positif)
 *   rose   → merah pena koreksi  (alfa, hapus, tunggakan)
 *   amber  → oker                (izin, peringatan, langganan)
 *
 * Konsekuensinya: satu berkas ini sudah mengubah rupa seluruh aplikasi, dan
 * penyisiran per halaman tinggal mengurus struktur, kerapatan, dan hierarki —
 * bukan mencari-ganti nama warna di 90 berkas.
 */

/** Netral hangat. Kertas HVS, bukan abu biru. */
const kertas = {
    50: '#f7f5f1',
    100: '#efebe4',
    200: '#e3ded4', // garis rambut — hampir seluruh border memakai ini
    300: '#cfc7b8',
    400: '#a79e90',
    500: '#7c7466', // teks sekunder
    600: '#5e5749',
    700: '#443e33', // teks isi
    800: '#2c2820',
    900: '#1a1712', // judul
    950: '#100e0a',
};

/** Tinta biru-hitam. Satu-satunya aksen: hanya untuk yang bisa diklik. */
const tinta = {
    50: '#eef3f8',
    100: '#dce6f0',
    200: '#bccee1',
    300: '#90aecc',
    400: '#5c87b0',
    500: '#356494',
    600: '#23486b',
    700: '#1c3a57',
    800: '#172f46',
    900: '#14283a',
    950: '#0d1a26',
};

/** Hijau papan tulis. Diredam supaya tabel padat tidak berubah jadi permen. */
const papan = {
    50: '#eef4ef',
    100: '#d8e7dc',
    200: '#b4d0bc',
    300: '#86b295',
    400: '#59906d',
    500: '#3d7452',
    600: '#2e6446',
    700: '#255238',
    800: '#1f422e',
    900: '#1a3626',
    950: '#0f2016',
};

/** Merah pena koreksi. */
const koreksi = {
    50: '#fbf0ee',
    100: '#f6dcd8',
    200: '#ebbab2',
    300: '#db8d80',
    400: '#c55f4e',
    500: '#af4130',
    600: '#9a3527',
    700: '#7e2b20',
    800: '#67241b',
    900: '#561f18',
    950: '#300f0c',
};

/** Oker. */
const oker = {
    50: '#faf4e8',
    100: '#f3e7ca',
    200: '#e6d096',
    300: '#d5b45d',
    400: '#c09733',
    500: '#a47d22',
    600: '#8a6317',
    700: '#6e4e14',
    800: '#5a4014',
    900: '#4b3613',
    950: '#2a1d09',
};

export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Instrument Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                slate: kertas,
                gray: kertas,
                zinc: kertas,
                neutral: kertas,
                stone: kertas,

                indigo: tinta,
                blue: tinta,
                violet: tinta,
                purple: tinta,
                sky: tinta,
                cyan: tinta,
                primary: tinta,

                emerald: papan,
                green: papan,
                teal: papan,
                lime: papan,

                rose: koreksi,
                red: koreksi,
                pink: koreksi,
                fuchsia: koreksi,
                orange: koreksi,

                amber: oker,
                yellow: oker,
            },

            /*
             * Sudut kecil, dan seluruh skalanya dimampatkan.
             *
             * rounded-xl dipakai 397 kali dan rounded-2xl 186 kali; memampatkan
             * skalanya di sini jauh lebih murah — dan jauh lebih sulit
             * terlewat — daripada menyunting 583 tempat.
             */
            borderRadius: {
                none: '0',
                sm: '2px',
                DEFAULT: '3px',
                md: '3px',
                lg: '4px',
                xl: '4px',
                '2xl': '6px',
                '3xl': '8px',
            },

            /*
             * Tanpa bayangan. Pemisahan dikerjakan garis, bukan gumpalan abu.
             *
             * Kecuali yang benar-benar mengambang di atas isi halaman: menu
             * avatar dan pemilih kelas perlu terbaca sebagai lapisan lain,
             * bukan sebagai kotak yang kebetulan menimpa. Itu pun tipis.
             */
            boxShadow: {
                xs: 'none',
                sm: 'none',
                DEFAULT: 'none',
                md: 'none',
                lg: 'none',
                xl: '0 1px 2px rgb(26 23 18 / 0.06), 0 12px 28px -14px rgb(26 23 18 / 0.28)',
                '2xl': '0 1px 2px rgb(26 23 18 / 0.06), 0 16px 36px -16px rgb(26 23 18 / 0.32)',
                inner: 'none',
                none: 'none',
            },

            /*
             * Gerak seperlunya. Yang tersisa hanya yang menyampaikan keadaan:
             * spin & ping untuk proses berjalan, dan satu peredupan halus.
             * Float, wiggle, bounce-slow, dan kawan-kawan dibuang — tidak satu
             * pun dipakai, dan tidak satu pun punya alasan untuk ada di layar
             * yang dibuka guru tiap hari.
             */
            animation: {
                'fade-in': 'fadeIn 0.15s ease-out forwards',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
            },

            maxWidth: {
                // Selebar buku, bukan selebar layar. Baris data yang melebar
                // sampai 1.400px memaksa mata melompat antar kolom.
                buku: '68rem',
            },
        },
    },
    plugins: [forms],
};
