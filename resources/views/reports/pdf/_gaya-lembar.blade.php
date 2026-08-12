{{--
    Gaya lembar profil siswa, dipakai berkas per-siswa maupun berkas sekelas.

    dompdf hanya memahami CSS 2.1: tidak ada flexbox, grid, gap, atau variabel
    warna. Setiap "batang" di bawah karena itu dibuat dari <div> berlebar persen
    di dalam <div> pembungkus — bukan SVG, bukan canvas, bukan pustaka grafik.
    Bentuk itu yang paling tua dan paling pasti dirender sama di semua versi.
--}}
<style>
    @page { margin: 12mm 15mm 12mm 15mm; }
    body {
        font-family: "DejaVu Sans", sans-serif;
        color: #1e293b;
        font-size: 8.5pt;
        line-height: 1.4;
        background: #ffffff;
    }
    h1, h2, h3, h4, p { margin: 0; }

    /* KOP HEADER */
    .kop {
        border-bottom: 2pt solid #0f172a;
        padding-bottom: 8pt;
        margin-bottom: 12pt;
    }
    .school-name {
        font-size: 11pt;
        font-weight: bold;
        text-transform: uppercase;
        color: #0f172a;
        letter-spacing: 0.5pt;
    }
    .school-sub { font-size: 7.5pt; color: #64748b; margin-top: 2pt; }
    .doc-title {
        text-align: right;
        font-size: 11pt;
        font-weight: bold;
        color: #059669;
        text-transform: uppercase;
    }
    .doc-subtitle { text-align: right; font-size: 7.5pt; color: #64748b; margin-top: 2pt; }

    /* BANNER + PAS FOTO */
    .banner {
        background: #f8fafc;
        border: 1pt solid #e2e8f0;
        border-left: 4pt solid #10b981;
        padding: 10pt 12pt;
        border-radius: 6pt;
        margin-bottom: 12pt;
    }
    .student-name { font-size: 13pt; font-weight: bold; color: #0f172a; text-transform: uppercase; }
    .student-meta { font-size: 8pt; color: #475569; margin-top: 3pt; }

    /* Ukuran pas foto 3:4 — sama seperti hasil potongan Student::simpanFoto(). */
    .foto {
        width: 66pt;
        height: 88pt;
        border: 1pt solid #cbd5e1;
        border-radius: 4pt;
    }
    /*
     * Kotak inisial dipakai saat siswa belum punya foto. Bukan kotak kosong:
     * ruang kosong pada dokumen resmi terbaca sebagai berkas yang belum selesai
     * dicetak, sedangkan inisial terbaca sebagai "fotonya memang belum ada".
     */
    .foto-kosong {
        width: 66pt;
        height: 88pt;
        border: 1pt dashed #cbd5e1;
        border-radius: 4pt;
        background: #f1f5f9;
        text-align: center;
        color: #94a3b8;
    }
    .foto-inisial { font-size: 22pt; font-weight: bold; padding-top: 24pt; }
    .foto-catatan { font-size: 5.5pt; text-transform: uppercase; letter-spacing: 0.3pt; }

    /* SECTION TITLES */
    .section-title {
        font-size: 9pt;
        font-weight: bold;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.3pt;
        margin-top: 10pt;
        margin-bottom: 5pt;
        padding-bottom: 3pt;
        border-bottom: 1pt solid #cbd5e1;
    }

    /* STATS GRID */
    .stats-grid { width: 100%; border-collapse: separate; border-spacing: 4pt; margin-bottom: 10pt; }
    .stat-card { background: #f8fafc; border: 1pt solid #e2e8f0; border-radius: 6pt; padding: 8pt; text-align: center; }
    .stat-val { font-size: 14pt; font-weight: bold; color: #0f172a; }
    .stat-label { font-size: 7pt; font-weight: bold; color: #64748b; text-transform: uppercase; margin-top: 2pt; }

    /* BATANG */
    .rel { width: 100%; background: #e2e8f0; height: 7pt; border-radius: 3.5pt; }
    .rel-isi { height: 7pt; border-radius: 3.5pt; }
    .rel-tipis { width: 100%; background: #f1f5f9; height: 5pt; border-radius: 2.5pt; }
    .rel-tipis-isi { height: 5pt; border-radius: 2.5pt; }
    .bar-tbl { width: 100%; border-collapse: collapse; margin-bottom: 10pt; }
    .bar-tbl td { font-size: 7.5pt; padding: 2.5pt 4pt; vertical-align: middle; border: 0; }
    .bar-label { color: #475569; }
    .bar-angka { font-weight: bold; text-align: right; }

    /* TABLES */
    .tbl { width: 100%; border-collapse: collapse; margin-bottom: 10pt; }
    .tbl th {
        background: #0f172a;
        color: #ffffff;
        font-size: 7.5pt;
        font-weight: bold;
        text-transform: uppercase;
        padding: 5pt 6pt;
        text-align: left;
        border: 1pt solid #0f172a;
    }
    .tbl td { font-size: 8pt; padding: 4.5pt 6pt; border: 1pt solid #e2e8f0; }
    .tbl tr:nth-child(even) { background: #f8fafc; }
    .c { text-align: center; }
    .r { text-align: right; }
    .tebal { font-weight: bold; }
    .kosong { text-align: center; color: #94a3b8; font-style: italic; padding: 8pt; }

    /* BIODATA BOX */
    .biodata-box { background: #ffffff; border: 1pt solid #e2e8f0; border-radius: 6pt; padding: 8pt 10pt; margin-bottom: 10pt; }
    .biodata-tbl { width: 100%; border-collapse: collapse; }
    .biodata-tbl td { padding: 3.5pt 4pt; font-size: 8pt; vertical-align: top; }
    .biodata-label { font-weight: bold; color: #475569; }

    /* KUTIPAN REFLEKSI */
    .kutipan { border-radius: 5pt; padding: 7pt 9pt; margin-bottom: 6pt; }
    .kutipan-judul { font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt; }
    .kutipan-isi { font-size: 8pt; font-style: italic; margin-top: 2pt; }
    .kutipan-teman { background: #f0f9ff; border: 1pt solid #bae6fd; }
    .kutipan-teman .kutipan-judul { color: #0369a1; }
    .kutipan-ortu { background: #fffbeb; border: 1pt solid #fde68a; }
    .kutipan-ortu .kutipan-judul { color: #b45309; }
    .kutipan-diri { background: #f8fafc; border: 1pt solid #e2e8f0; }
    .kutipan-diri .kutipan-judul { color: #475569; }

    /* BADGES */
    .badge { display: inline-block; padding: 1.5pt 5pt; border-radius: 3pt; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-danger { background: #ffe4e6; color: #9f1239; }
    .badge-info { background: #e0f2fe; color: #075985; }

    /* SIGNATURE */
    .sig-table { width: 100%; margin-top: 15pt; border-collapse: collapse; }
    .sig-cell { width: 50%; text-align: center; vertical-align: top; font-size: 8.5pt; }
    .sig-space { height: 18mm; }
    .sig-line { border-top: 1pt solid #0f172a; width: 65%; margin: 0 auto; padding-top: 2pt; font-weight: bold; }

    /* Pemisah antar siswa pada berkas sekelas. */
    .lembar-baru { page-break-before: always; }
</style>
