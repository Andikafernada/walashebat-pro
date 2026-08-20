import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/*
 * KERTAS HANGAT
 *
 * Arah baru: dasar krem gading seperti kertas HVS, lembar putih yang
 * mengambang di atasnya, dan SATU aksen terracotta/rust — warna tinta
 * stempel dan sampul map arsip sekolah. Warna semantik (hijau/kuning/merah)
 * dinaikkan sedikit kehangatannya (moss/mustard/bata) supaya sekeluarga
 * dengan dasarnya, bukan hijau-kuning-merah klinis di atas kertas hangat.
 *
 * Menggantikan "TERANG MODERN" (dasar abu sejuk, aksen lembayung) yang
 * berdiri di sini sebelumnya — pemilik memutuskan arah itu belum pas dan
 * minta sesuatu yang terasa seperti buku catatan wali kelas fisik, bukan
 * dashboard SaaS.
 *
 * NAMA WARNANYA SENGAJA TIDAK DIGANTI, dengan alasan yang sama seperti versi
 * sebelumnya: ~4.000 pemakaian kelas warna harfiah di 90 berkas Blade.
 * Yang diganti NILAINYA:
 *
 *   slate  → netral hangat (kertas, garis, tinta)
 *   indigo → terracotta, satu-satunya aksen
 *   emerald→ hijau lumut   (hadir, lunas, beres)
 *   rose   → merah bata    (alfa, hapus, tunggakan)
 *   amber  → kuning mustar (izin, peringatan, langganan)
 */

/* Netral hangat, dibiaskan ke cokelat kertas — bukan abu netral murni. */
const netral = {
    50: '#faf6ee',  // dasar halaman — kertas HVS
    100: '#f3ecdd',
    200: '#e8dcc6', // garis — hampir seluruh border memakai ini
    300: '#d6c3a3',
    400: '#b39d7d',
    500: '#8a7860', // teks sekunder
    600: '#6b5b48',
    700: '#4f4335', // teks isi
    800: '#362d23',
    900: '#241d16', // judul
    950: '#150f0a',
};

/** Terracotta/rust. Satu-satunya aksen: hanya untuk yang bisa diklik atau aktif. */
const aksen = {
    50: '#fdf3ec',
    100: '#fbe4d2',
    200: '#f5c7a8',
    300: '#eca274',
    400: '#dc7d49',
    500: '#c4622d',
    600: '#ad4f22', // tombol utama
    700: '#8c3e1a',
    800: '#6f3216',
    900: '#5a2913',
    950: '#33160a',
};

/** Hijau lumut — hadir, lunas, beres. */
const hijau = {
    50: '#f3f6ec',
    100: '#e3ebd1',
    200: '#c8d9a8',
    300: '#a8c078',
    400: '#8aa857',
    500: '#708f44',
    600: '#597237',
    700: '#46592c',
    800: '#384624',
    900: '#2e3a1f',
    950: '#191f10',
};

/** Merah bata — alfa, hapus, tunggakan. */
const merah = {
    50: '#fbefec',
    100: '#f6dad2',
    200: '#ebb4a2',
    300: '#de8a6e',
    400: '#cb6244',
    500: '#b4472a',
    600: '#963a21',
    700: '#782e1b',
    800: '#602719',
    900: '#4e2117',
    950: '#2a100b',
};

/** Kuning mustar — izin, peringatan, langganan. */
const kuning = {
    50: '#fbf5e7',
    100: '#f5e6be',
    200: '#ebcd82',
    300: '#dfb350',
    400: '#ce9a34',
    500: '#b07e24',
    600: '#8e651e',
    700: '#6e4e19',
    800: '#573f16',
    900: '#473413',
    950: '#271c09',
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
                /*
                 * Serif editorial untuk judul — dipakai TERBATAS (lihat
                 * app.css: h1–h4, .blok__judul, .page-header__title saja).
                 * Newsreader sudah dilayani sendiri sejak awal untuk
                 * landing.blade.php, jadi tidak ada berkas font baru yang
                 * diunduh — halaman yang belum pernah memakainya kini ikut
                 * memakai berkas yang sudah pernah diambil peramban.
                 */
                display: ['"Newsreader"', ...defaultTheme.fontFamily.serif],
            },

            colors: {
                slate: netral,
                gray: netral,
                zinc: netral,
                neutral: netral,
                stone: netral,

                indigo: aksen,
                blue: aksen,
                violet: aksen,
                purple: aksen,
                sky: aksen,
                cyan: aksen,
                primary: aksen,

                emerald: hijau,
                green: hijau,
                teal: hijau,
                lime: hijau,

                rose: merah,
                red: merah,
                pink: merah,
                fuchsia: merah,
                orange: merah,

                amber: kuning,
                yellow: kuning,
            },

            /*
             * Sudut membulat, ditarik satu tingkat lebih kecil dari sebelumnya
             * — kertas dan kartu arsip tidak bersudut sebesar tombol aplikasi
             * ponsel. Skalanya tetap dipetakan ulang di sini, bukan disunting
             * di 583 tempat, dengan alasan yang sama seperti warna di atas.
             */
            borderRadius: {
                none: '0',
                sm: '4px',
                DEFAULT: '6px',
                md: '8px',
                lg: '10px',
                xl: '12px',
                '2xl': '16px',
                '3xl': '20px',
            },

            /*
             * Bayangan lembut, nadanya cokelat-gelap sekarang (dulu
             * biru-gelap) — bayangan sejuk di atas dasar kertas hangat
             * terbaca kotor, sama seperti bayangan hitam murni di atas dasar
             * berbias lembayung dulu.
             */
            boxShadow: {
                xs: '0 1px 2px rgb(40 32 20 / 0.05)',
                sm: '0 1px 2px rgb(40 32 20 / 0.05), 0 4px 10px -8px rgb(40 32 20 / 0.30)',
                DEFAULT: '0 1px 2px rgb(40 32 20 / 0.05), 0 10px 22px -18px rgb(40 32 20 / 0.40)',
                md: '0 1px 2px rgb(40 32 20 / 0.05), 0 10px 22px -18px rgb(40 32 20 / 0.40)',
                lg: '0 1px 2px rgb(40 32 20 / 0.06), 0 16px 32px -20px rgb(40 32 20 / 0.45)',
                xl: '0 1px 2px rgb(40 32 20 / 0.07), 0 24px 44px -24px rgb(40 32 20 / 0.50)',
                '2xl': '0 1px 2px rgb(40 32 20 / 0.07), 0 32px 60px -28px rgb(40 32 20 / 0.55)',
                inner: 'inset 0 1px 2px rgb(40 32 20 / 0.07)',
                none: 'none',
            },

            /*
             * GERAK — kosakata empat kata, semuanya hanya menyentuh transform
             * dan opacity supaya ponsel kelas menengah menggambarnya di GPU
             * tanpa menghitung ulang tata letak.
             *
             *   naik     kartu/baris muncul: naik sedikit lalu diam
             *   pop      lencana mendarat dengan satu pantulan kecil
             *   garis    garis ditarik dari kiri ke kanan
             *   denyut   satu tarikan perhatian, hanya untuk yang menunggu
             *
             * Yang TIDAK ada di sini: float, wiggle, bounce tanpa henti. Gerak
             * yang berulang selamanya bukan gerak, melainkan gangguan — dan
             * halaman ini dibuka guru tiap pagi, bukan sekali seumur hidup.
             *
             * Pemakaiannya diatur app.css, termasuk penghormatan pada
             * prefers-reduced-motion yang mematikan semuanya sekaligus.
             */
            animation: {
                'fade-in': 'fadeIn 0.15s ease-out forwards',
                naik: 'naik 0.26s cubic-bezier(0.22, 1, 0.36, 1) both',
                pop: 'pop 0.28s cubic-bezier(0.34, 1.56, 0.64, 1) both',
                garis: 'garis 0.42s cubic-bezier(0.22, 1, 0.36, 1) both',
                denyut: 'denyut 2.4s ease-in-out infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },

                /* Naik 8px sambil muncul. Sependek itu supaya terasa sebagai
                   "sudah ada, baru terbaca", bukan sebagai benda yang terbang. */
                naik: {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },

                /* Lencana mendarat: sedikit lebih kecil, melewati ukurannya,
                   lalu berhenti. Satu pantulan, tanpa putaran. */
                pop: {
                    '0%': { opacity: '0', transform: 'scale(0.86)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },

                /* Ditarik dari kiri. Dipakai garis bawah judul dan pemisah. */
                garis: {
                    '0%': { transform: 'scaleX(0)' },
                    '100%': { transform: 'scaleX(1)' },
                },

                /* Hanya untuk yang benar-benar menunggu tindakan guru. */
                denyut: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.55' },
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
