<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'push_enabled',
        'push_attendance_reminder',
        'push_new_violation',
        'push_low_cashbook',
        'push_daily_summary',
        'push_subscription',
    ];

    protected function casts(): array
    {
        return [
            'push_enabled' => 'boolean',
            'push_attendance_reminder' => 'boolean',
            'push_new_violation' => 'boolean',
            'push_low_cashbook' => 'boolean',
            'push_daily_summary' => 'boolean',
            'push_subscription' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Apakah push notification enabled? */
    public function isPushEnabled(): bool
    {
        return $this->push_enabled && $this->hasPushSubscription();
    }

    /** Apakah sudah ada subscription? */
    public function hasPushSubscription(): bool
    {
        return ! empty($this->push_subscription);
    }

    /** Simpan push subscription dari browser */
    public function updatePushSubscription(array $subscription): void
    {
        $this->update(['push_subscription' => $subscription]);
    }

    /** Hapus push subscription */
    public function removePushSubscription(): void
    {
        $this->update(['push_subscription' => null]);
    }
}
