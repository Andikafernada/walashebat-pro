{{--
    Gaya Lembar Portofolio Komprehensif Siswa (PDF DOMPDF Compatible)
    Desain Formal, Presisi, Elegan & Ramah Cetak A4.
--}}
<style>
    @page { 
        size: A4 portrait;
        margin: 10mm 12mm 10mm 12mm; 
    }
    body {
        font-family: "DejaVu Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
        color: #0f172a;
        font-size: 8pt;
        line-height: 1.35;
        background: #ffffff;
        -webkit-print-color-adjust: exact;
    }
    h1, h2, h3, h4, p { margin: 0; }

    /* KOP DOKUMEN */
    .kop {
        border-bottom: 2pt solid #047857;
        padding-bottom: 6pt;
        margin-bottom: 10pt;
    }
    .school-name {
        font-size: 11pt;
        font-weight: 900;
        text-transform: uppercase;
        color: #064e3b;
        letter-spacing: 0.3pt;
    }
    .school-sub { 
        font-size: 7.5pt; 
        color: #64748b; 
        margin-top: 1.5pt; 
        font-weight: 500;
    }
    .doc-title {
        text-align: right;
        font-size: 10.5pt;
        font-weight: 900;
        color: #047857;
        text-transform: uppercase;
    }
    .doc-subtitle { 
        text-align: right; 
        font-size: 7.5pt; 
        color: #64748b; 
        margin-top: 1.5pt; 
        font-weight: 600;
    }

    /* BANNER IDENTITAS & PAS FOTO */
    .banner {
        background: #f8fafc;
        border: 1pt solid #cbd5e1;
        border-left: 4pt solid #059669;
        padding: 8pt 10pt;
        border-radius: 6pt;
        margin-bottom: 10pt;
    }
    .student-name { 
        font-size: 12pt; 
        font-weight: 900; 
        color: #0f172a; 
        text-transform: uppercase; 
        line-height: 1.2;
    }
    .student-meta { 
        font-size: 7.5pt; 
        color: #475569; 
        margin-top: 3pt; 
        line-height: 1.4;
    }

    .foto {
        width: 60pt;
        height: 80pt;
        border: 1pt solid #cbd5e1;
        border-radius: 4pt;
    }
    .foto-kosong {
        width: 60pt;
        height: 80pt;
        border: 1pt dashed #cbd5e1;
        border-radius: 4pt;
        background: #f1f5f9;
        text-align: center;
        color: #64748b;
    }
    .foto-inisial { 
        font-size: 20pt; 
        font-weight: 900; 
        padding-top: 20pt; 
        color: #047857;
    }
    .foto-catatan { 
        font-size: 5pt; 
        text-transform: uppercase; 
        letter-spacing: 0.3pt; 
        color: #94a3b8;
    }

    /* SECTION TITLES */
    .section-title {
        font-size: 8.5pt;
        font-weight: 900;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
        margin-top: 8pt;
        margin-bottom: 4pt;
        padding-bottom: 2pt;
        border-bottom: 1pt solid #cbd5e1;
    }

    /* STATS GRID */
    .stats-grid { 
        width: 100%; 
        border-collapse: separate; 
        border-spacing: 3pt; 
        margin-bottom: 8pt; 
    }
    .stat-card { 
        background: #f8fafc; 
        border: 1pt solid #e2e8f0; 
        border-radius: 5pt; 
        padding: 5pt 4pt; 
        text-align: center; 
    }
    .stat-val { 
        font-size: 12pt; 
        font-weight: 900; 
        color: #0f172a; 
    }
    .stat-label { 
        font-size: 6.5pt; 
        font-weight: 800; 
        color: #64748b; 
        text-transform: uppercase; 
        margin-top: 1pt; 
    }

    /* PROGRESS BAR */
    .rel { width: 100%; background: #e2e8f0; height: 6pt; border-radius: 3pt; overflow: hidden; }
    .rel-isi { height: 6pt; border-radius: 3pt; }
    .rel-tipis { width: 100%; background: #e2e8f0; height: 4.5pt; border-radius: 2.25pt; overflow: hidden; }
    .rel-tipis-isi { height: 4.5pt; border-radius: 2.25pt; }
    .bar-tbl { width: 100%; border-collapse: collapse; margin-bottom: 6pt; }
    .bar-tbl td { font-size: 7.5pt; padding: 2pt 3pt; vertical-align: middle; border: 0; }
    .bar-label { color: #334155; font-weight: 600; }
    .bar-angka { font-weight: 800; text-align: right; }

    /* TABLES */
    .tbl { width: 100%; border-collapse: collapse; margin-bottom: 8pt; }
    .tbl th {
        background: #0f172a;
        color: #ffffff;
        font-size: 7pt;
        font-weight: 900;
        text-transform: uppercase;
        padding: 4pt 5pt;
        text-align: left;
        border: 1pt solid #0f172a;
    }
    .tbl td { 
        font-size: 7.5pt; 
        padding: 3.5pt 5pt; 
        border: 1pt solid #e2e8f0; 
    }
    .tbl tr:nth-child(even) { background: #f8fafc; }
    .c { text-align: center; }
    .r { text-align: right; }
    .tebal { font-weight: 800; }
    .kosong { text-align: center; color: #94a3b8; font-style: italic; padding: 6pt; font-size: 7.5pt; }

    /* KUTIPAN REFLEKSI */
    .kutipan { border-radius: 4pt; padding: 5pt 7pt; margin-bottom: 4pt; }
    .kutipan-judul { font-size: 6pt; font-weight: 900; text-transform: uppercase; letter-spacing: 0.3pt; }
    .kutipan-isi { font-size: 7.5pt; font-style: italic; margin-top: 1.5pt; color: #1e293b; }
    .kutipan-teman { background: #f0f9ff; border: 1pt solid #bae6fd; }
    .kutipan-teman .kutipan-judul { color: #0369a1; }
    .kutipan-ortu { background: #fffbeb; border: 1pt solid #fde68a; }
    .kutipan-ortu .kutipan-judul { color: #b45309; }
    .kutipan-diri { background: #f8fafc; border: 1pt solid #e2e8f0; }
    .kutipan-diri .kutipan-judul { color: #475569; }

    /* BADGES */
    .badge { display: inline-block; padding: 1pt 4pt; border-radius: 3pt; font-size: 6.5pt; font-weight: 800; text-transform: uppercase; }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-danger { background: #ffe4e6; color: #9f1239; }
    .badge-info { background: #e0f2fe; color: #075985; }

    /* SIGNATURE */
    .sig-table { width: 100%; margin-top: 12pt; border-collapse: collapse; }
    .sig-cell { width: 50%; text-align: center; vertical-align: top; font-size: 8pt; }
    .sig-space { height: 16mm; }
    .sig-line { border-top: 1pt solid #0f172a; width: 65%; margin: 0 auto; padding-top: 2pt; font-weight: 800; }

    .lembar-baru { page-break-before: always; }
</style>
