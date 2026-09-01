<style>
    @page { 
        size: A4 portrait;
        margin: 10mm 12mm 10mm 12mm; 
    }
    * {
        box-sizing: border-box;
    }
    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        color: #0f172a;
        font-size: 8pt;
        line-height: 1.35;
        background: #ffffff;
        -webkit-print-color-adjust: exact;
        margin: 0;
        padding: 0;
    }
    h1, h2, h3, h4, p { margin: 0; padding: 0; }

    /* HEADER KOP SEKOLAH */
    .header-box {
        border-bottom: 2pt solid #047857;
        padding-bottom: 6pt;
        margin-bottom: 8pt;
    }
    .school-title {
        font-size: 11pt;
        font-weight: 900;
        color: #064e3b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        line-height: 1.15;
    }
    .school-address {
        font-size: 7pt;
        color: #64748b;
        margin-top: 1.5pt;
    }
    .doc-badge {
        font-size: 10pt;
        font-weight: 900;
        color: #047857;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        text-align: right;
    }
    .doc-period {
        font-size: 7.5pt;
        font-weight: 700;
        color: #334155;
        text-align: right;
        margin-top: 1.5pt;
    }

    /* PROFILE BANNER CARD */
    .profile-card {
        background-color: #f8fafc;
        border: 1pt solid #cbd5e1;
        border-left: 4pt solid #059669;
        border-radius: 6pt;
        padding: 8pt 10pt;
        margin-bottom: 8pt;
    }
    .student-avatar {
        width: 55pt;
        height: 70pt;
        border-radius: 4pt;
        border: 1.5pt solid #059669;
        object-fit: cover;
    }
    .student-avatar-empty {
        width: 55pt;
        height: 70pt;
        border-radius: 4pt;
        border: 1.5pt dashed #cbd5e1;
        background-color: #f1f5f9;
        text-align: center;
        vertical-align: middle;
    }
    .avatar-initials {
        font-size: 20pt;
        font-weight: 900;
        color: #047857;
        padding-top: 16pt;
    }
    .avatar-label {
        font-size: 5pt;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
    }
    .student-name-main {
        font-size: 12pt;
        font-weight: 900;
        color: #064e3b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        line-height: 1.15;
    }

    /* BADGE PILLS */
    .badge-pill {
        display: inline-block;
        padding: 1.5pt 5pt;
        border-radius: 3pt;
        font-size: 6.5pt;
        font-weight: 800;
        text-transform: uppercase;
    }
    .badge-emerald { background-color: #d1fae5; color: #065f46; border: 0.5pt solid #a7f3d0; }
    .badge-blue { background-color: #dbeafe; color: #1e40af; border: 0.5pt solid #bfdbfe; }
    .badge-rose { background-color: #ffe4e6; color: #9f1239; border: 0.5pt solid #fecdd3; }
    .badge-amber { background-color: #fef3c7; color: #92400e; border: 0.5pt solid #fde68a; }

    /* STATS 4-BOX ROW */
    .kpi-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 4pt;
        margin-bottom: 8pt;
    }
    .kpi-box {
        border-radius: 5pt;
        padding: 5pt 6pt;
        text-align: center;
        border: 1pt solid #e2e8f0;
    }
    .kpi-val {
        font-size: 13pt;
        font-weight: 900;
        line-height: 1.1;
    }
    .kpi-title {
        font-size: 6.2pt;
        font-weight: 800;
        text-transform: uppercase;
        color: #64748b;
        margin-top: 1pt;
        letter-spacing: 0.3px;
    }

    /* SECTION TITLES */
    .section-header {
        font-size: 8pt;
        font-weight: 900;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 3pt 6pt;
        background-color: #f1f5f9;
        border-left: 3.5pt solid #059669;
        border-bottom: 0.5pt solid #cbd5e1;
        margin-top: 8pt;
        margin-bottom: 5pt;
    }

    /* DATA TABLES */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6pt;
    }
    .data-table th {
        background-color: #0f172a;
        color: #ffffff;
        font-size: 7pt;
        font-weight: 800;
        text-transform: uppercase;
        padding: 3.5pt 5pt;
        text-align: left;
        border: 0.5pt solid #0f172a;
    }
    .data-table td {
        font-size: 7.5pt;
        padding: 3pt 5pt;
        border: 0.5pt solid #e2e8f0;
        color: #334155;
    }
    .data-table tr:nth-child(even) td {
        background-color: #f8fafc;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .font-bold { font-weight: 800; }
    .kosong { text-align: center; color: #94a3b8; font-style: italic; padding: 6pt; font-size: 7.5pt; }

    /* 2-COLUMN TABLE */
    .col-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6pt;
    }
    .col-cell {
        vertical-align: top;
        padding: 0 4pt;
    }

    /* SPEECH BUBBLES */
    .quote-box {
        border-radius: 4pt;
        padding: 5pt 7pt;
        margin-bottom: 4pt;
        font-size: 7.2pt;
        border: 0.5pt solid #cbd5e1;
    }
    .quote-self { background-color: #f8fafc; border-left: 3pt solid #2563eb; }
    .quote-peer { background-color: #f0fdf4; border-left: 3pt solid #10b981; }
    .quote-parent { background-color: #fffbeb; border-left: 3pt solid #f59e0b; }
    .quote-teacher { background-color: #faf5ff; border-left: 3pt solid #9333ea; }

    /* SIGNATURE BLOCK */
    .sig-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12pt;
    }
    .sig-td {
        width: 50%;
        text-align: center;
        vertical-align: top;
        font-size: 7.8pt;
    }
    .sig-space {
        height: 16mm;
    }
    .sig-underline {
        border-bottom: 1pt solid #0f172a;
        width: 65%;
        margin: 0 auto;
        font-weight: 800;
        padding-bottom: 1pt;
    }

    .page-break {
        page-break-after: always;
    }
</style>
