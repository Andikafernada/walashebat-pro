<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'icon',
        'color',
        'action_url',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Tandai sudah dibaca */
    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    /** Apakah sudah dibaca? */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /** Scope: belum dibaca */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /** Scope: sudah dibaca */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Helper untuk membuat notifikasi baru
     */
    public static function createForUser(
        User $user,
        string $title,
        string $body,
        string $type,
        ?string $actionUrl = null,
        array $data = [],
        string $icon = 'bell',
        string $color = 'indigo'
    ): self {
        return $user->userNotifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'color' => $color,
            'action_url' => $actionUrl,
            'data' => $data,
        ]);
    }
}
