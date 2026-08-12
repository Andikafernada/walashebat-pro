<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Kas - {{ $class->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 16px; margin-bottom: 5px; }
        .header p { font-size: 10px; color: #666; }
        .summary { display: flex; gap: 20px; margin-bottom: 20px; }
        .summary-card { flex: 1; padding: 15px; border-radius: 4px; text-align: center; }
        .summary-card.income { background: #dcfce7; border: 2px solid #16a34a; }
        .summary-card.expense { background: #fee2e2; border: 2px solid #dc2626; }
        .summary-card.balance { background: #e0e7ff; border: 2px solid #4f46e5; }
        .summary-card .label { font-size: 10px; color: #666; margin-bottom: 5px; }
        .summary-card .amount { font-size: 16px; font-weight: bold; }
        .summary-card.income .amount { color: #16a34a; }
        .summary-card.expense .amount { color: #dc2626; }
        .summary-card.balance .amount { color: #4f46e5; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 8px 10px; }
        th { background: #f0f0f0; font-weight: bold; text-align: center; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .income { color: #16a34a; }
        .expense { color: #dc2626; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; }
        .signature-table { width: 100%; margin-top: 40px; }
        .signature-table td { text-align: center; vertical-align: top; padding: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BUKU KAS KELAS</h1>
        <p>{{ $class->name }} - {{ $class->major }}</p>
        <p>Tahun Ajaran {{ now()->year }}/{{ now()->year + 1 }}</p>
    </div>

    <div class="summary">
        <div class="summary-card income">
            <p class="label">Total Pemasukan</p>
            <p class="amount">Rp {{ number_format($totalIncome, 0, ',', '.') }}</p>
        </div>
        <div class="summary-card expense">
            <p class="label">Total Pengeluaran</p>
            <p class="amount">Rp {{ number_format($totalExpense, 0, ',', '.') }}</p>
        </div>
        <div class="summary-card balance">
            <p class="label">Saldo</p>
            <p class="amount">Rp {{ number_format($totalIncome - $totalExpense, 0, ',', '.') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 80px;">Tanggal</th>
                <th style="width: 80px;">Jenis</th>
                <th class="text-left">Keterangan</th>
                <th style="width: 120px;">Pemasukan</th>
                <th style="width: 120px;">Pengeluaran</th>
                <th style="width: 130px;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td style="text-align: center;">{{ $transaction['no'] }}</td>
                    <td>{{ $transaction['date']->format('d/m/Y') }}</td>
                    <td style="text-align: center;">{{ $transaction['type'] === 'income' ? 'Masuk' : 'Keluar' }}</td>
                    <td class="text-left">{{ $transaction['description'] }}</td>
                    <td class="text-right {{ $transaction['type'] === 'income' ? 'income' : '' }}">
                        {{ $transaction['type'] === 'income' ? 'Rp ' . number_format($transaction['amount'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right {{ $transaction['type'] === 'expense' ? 'expense' : '' }}">
                        {{ $transaction['type'] === 'expense' ? 'Rp ' . number_format($transaction['amount'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right">Rp {{ number_format($transaction['balance_after'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">Tidak ada transaksi</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d M Y, H:i') }} WIB</p>
        <p>Dicetak oleh: {{ ($user->name ?? $class->user->name ?? "Wali Kelas") }}</p>
    </div>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <p>Mengetahui,</p>
                <p>Bendahara Kelas</p>
                <div style="height: 60px;"></div>
                <p>_______________________</p>
            </td>
            <td style="width: 50%;">
                {{-- users.school_city, BUKAN $class->city: tabel classes tidak punya
                     kolom kota sama sekali, sehingga keempat berkas ekspor selalu
                     mencetak kata harfiah "Kota" — placeholder yang terkirim
                     sebagai dokumen bertanda tangan. --}}
                @php $kota = $user->school_city ?? $class->user->school_city; @endphp
                <p>{{ $kota ? $kota.', ' : '' }}{{ now()->translatedFormat('d F Y') }}</p>
                <p>Wali Kelas</p>
                <div style="height: 60px;"></div>
                <p>{{ ($user->name ?? $class->user->name ?? "Wali Kelas") }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
