<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProof extends Model
{
    use HasFactory;

    /**
     * Harga langganan PRO, per bulan, dalam rupiah.
     *
     * SATU-SATUNYA tempat angka ini boleh ditulis.
     *
     * Sebelumnya ia hidup di empat tempat yang saling tidak tahu: halaman muka
     * dan formulir unggah bukti menyebut Rp 10.000 kepada guru, controller
     * menyimpan Rp 19.000 ke kolom amount, dan panel operator melabelinya
     * "PRO BULANAN (19rb)". Operator karena itu membaca nominal yang berbeda
     * dari yang guru diberi tahu dan benar-benar transfer — pada data yang
     * menentukan apakah pembayaran seseorang diterima atau ditolak.
     */
    const HARGA_BULANAN = 10000;

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'plan_type',
        'amount',
        'proof_image',
        'bank_name',
        'sender_name',
        // Status fields below should NOT be fillable via mass assignment
        // They are set by admin approval process only
        // 'status',
        // 'rejection_reason',
        // 'approved_by',
        // 'approved_at',
    ];

    /*
     * Tidak ada $guarded, sama seperti di App\Models\User: Laravel mengabaikan
     * $guarded sepenuhnya begitu $fillable terisi, jadi daftar yang dulu ada di
     * sini tidak melindungi apa pun dan hanya memberi rasa aman palsu.
     *
     * Yang benar-benar menjaga 'status', 'granted_months', 'approved_by', dan
     * 'approved_at' adalah tidak dicantumkannya kolom itu di $fillable di atas.
     * Semuanya ditulis lewat forceFill() di AdminSubscriptionController.
     */

    protected $casts = [
        'approved_at' => 'datetime',
        'amount' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
