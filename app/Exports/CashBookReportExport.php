<?php

namespace App\Exports;

use App\Models\CashBook;
use App\Models\Classroom;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashBookReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly Classroom $class,
        private readonly Collection $transactions
    ) {}

    public function collection(): Collection
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Jenis',
            'Keterangan',
            'Pemasukan',
            'Pengeluaran',
            'Saldo',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction['no'],
            $transaction['date']->format('d/m/Y'),
            // 'in'/'out' — enum kolomnya memang itu, lihat migrasi cash_books.
            $transaction['type'] === 'in' ? 'Pemasukan' : 'Pengeluaran',
            $transaction['description'],
            $transaction['type'] === 'in' ? $transaction['amount_formatted'] : '-',
            $transaction['type'] === 'out' ? $transaction['amount_formatted'] : '-',
            $transaction['balance_formatted'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E5E7EB']],
                'alignment' => ['horizontal' => 'center'],
            ],
            'A:G' => [
                'alignment' => ['vertical' => 'center'],
            ],
            'E:F' => [
                'alignment' => ['horizontal' => 'right'],
            ],
            'G' => [
                'alignment' => ['horizontal' => 'right'],
            ],
        ];
    }
}
