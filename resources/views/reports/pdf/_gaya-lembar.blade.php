<style>
    @page { 
        size: A4 portrait;
        margin: 8mm 10mm 8mm 10mm; 
    }
    * {
        box-sizing: border-box;
    }
    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        color: #1e293b;
        font-size: 7.5pt;
        line-height: 1.3;
        background: #ffffff;
        -webkit-print-color-adjust: exact;
        margin: 0;
        padding: 0;
    }
    h1, h2, h3, h4, p { margin: 0; padding: 0; }

    /* HEADER & KOP PERUSAHAAN/SEKOLAH */
    .header-box {
        border-bottom: 2.5pt solid #059669;
        padding-bottom: 6pt;
        margin-bottom: 8pt;
    }
    .school-title {
        font-size: 11pt;
        font-weight: 900;
        color: #064e3b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.1;
    }
    .school-address {
        font-size: 6.8pt;
        color: #64748b;
        margin-top: 1.5pt;
    }
    .doc-badge {
        font-size: 9.5pt;
        font-weight: 900;
        color: #059669;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: right;
    }
    .doc-period {
        font-size: 7pt;
        font-weight: 700;
        color: #475569;
        text-align: right;
        margin-top: 1.5pt;
    }

    /* PROFILE BANNER CARD */
    .profile-card {
        background-color: #f0fdf4;
        border: 1pt solid #a7f3d0;
        border-radius: 6pt;
        padding: 6pt 8pt;
        margin-bottom: 8pt;
    }
    .student-avatar {
        width: 48pt;
        height: 60pt;
        border-radius: 4pt;
        border: 1.5pt solid #10b981;
        object-fit: cover;
    }
    .student-avatar-empty {
        width: 48pt;
        height: 60pt;
        border-radius: 4pt;
        border: 1.5pt dashed #6ee7b7;
        background-color: #ecfdf5;
        text-align: center;
        vertical-align: middle;
    }
    .avatar-initials {
        font-size: 16pt;
        font-weight: 900;
        color: #047857;
        padding-top: 12pt;
    }
    .avatar-label {
        font-size: 4.5pt;
        color: #059669;
        font-weight: 700;
        text-transform: uppercase;
    }
    .student-name-main {
        font-size: 11pt;
        font-weight: 900;
        color: #064e3b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        line-height: 1.1;
    }
    .badge-pill {
        display: inline-block;
        padding: 1pt 4pt;
        border-radius: 3pt;
        font-size: 6pt;
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
        padding: 4pt 6pt;
        text-align: center;
        border: 1pt solid #e2e8f0;
    }
    .kpi-val {
        font-size: 12pt;
        font-weight: 900;
        line-height: 1.1;
    }
    .kpi-title {
        font-size: 5.8pt;
        font-weight: 800;
        text-transform: uppercase;
        color: #64748b;
        margin-top: 1pt;
        letter-spacing: 0.3px;
    }

    /* SECTION TITLES */
    .section-header {
        font-size: 7.5pt;
        font-weight: 900;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2.5pt 5pt;
        background-color: #f8fafc;
        border-left: 3pt solid #059669;
        border-bottom: 0.5pt solid #e2e8f0;
        margin-top: 6pt;
        margin-bottom: 4pt;
    }

    /* 2-COLUMN LAYOUT TABLE */
    .col-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6pt;
    }
    .col-cell {
        vertical-align: top;
        padding: 0 3pt;
    }

    /* DATA TABLES */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5pt;
    }
    .data-table th {
        background-color: #0f172a;
        color: #ffffff;
        font-size: 6.5pt;
        font-weight: 800;
        text-transform: uppercase;
        padding: 3pt 4pt;
        text-align: left;
        border: 0.5pt solid #0f172a;
    }
    .data-table td {
        font-size: 7pt;
        padding: 2.5pt 4pt;
        border: 0.5pt solid #e2e8f0;
        color: #334155;
    }
    .data-table tr:nth-child(even) td {
        background-color: #f8fafc;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .font-bold { font-weight: 800; }

    /* PROGRESS BAR */
    .progress-bar-bg {
        width: 100%;
        background-color: #e2e8f0;
        height: 4pt;
        border-radius: 2pt;
        overflow: hidden;
    }
    .progress-bar-fill {
        height: 4pt;
        border-radius: 2pt;
    }

    /* SPEECH BUBBLE CARDS FOR REFLECTION */
    .quote-box {
        border-radius: 4pt;
        padding: 4pt 6pt;
        margin-bottom: 3pt;
        font-size: 6.8pt;
        border: 0.5pt solid #cbd5e1;
    }
    .quote-self { background-color: #f8fafc; border-left: 2.5pt solid #3b82f6; }
    .quote-peer { background-color: #f0fdf4; border-left: 2.5pt solid #10b981; }
    .quote-parent { background-color: #fffbeb; border-left: 2.5pt solid #f59e0b; }

    /* SIGNATURE BLOCK */
    .sig-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8pt;
    }
    .sig-td {
        width: 50%;
        text-align: center;
        vertical-align: top;
        font-size: 7.2pt;
    }
    .sig-space {
        height: 13mm;
    }
    .sig-underline {
        border-bottom: 1pt solid #0f172a;
        width: 60%;
        margin: 0 auto;
        font-weight: 800;
        padding-bottom: 1pt;
    }
</style>
