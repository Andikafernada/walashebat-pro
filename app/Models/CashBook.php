<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBook extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'class_id', 'student_id', 'transaction_date', 'type',
        'amount', 'description', 'balance_after',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    /**
     * Siswa pembayar, bila transaksi ini memang atas nama seseorang.
     *
     * Bisa null untuk transaksi umum (beli spidol, sumbangan kelas), dan bisa
     * berubah menjadi null bila siswanya dihapus — riwayat uangnya tetap utuh.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
