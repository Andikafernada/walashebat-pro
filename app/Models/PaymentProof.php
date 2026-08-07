<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProof extends Model
{
    use HasFactory;

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
