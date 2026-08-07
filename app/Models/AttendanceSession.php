<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class AttendanceSession extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'class_id', 'schedule_id', 'title', 'mapel', 'materi',
        'session_date', 'sequence',
        'token', 'pin_hash', 'expires_at', 'status', 'submitted_at', 'submitted_ip',
        'delivery_status', 'delivery_target', 'delivery_error', 'delivered_at',
    ];

    protected $hidden = ['pin_hash'];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'sequence' => 'integer',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** Jumlah sesi yang sudah ada untuk sebuah kelas pada satu tanggal. */
    public static function countForDay(int $classId, ?\DateTimeInterface $date = null): int
    {
        return static::withoutTenant()
            ->where('class_id', $classId)
            ->whereDate('session_date', ($date ?? now())->format('Y-m-d'))
            ->count();
    }

    /** Urutan berikutnya untuk kelas pada satu tanggal (1 bila belum ada). */
    public static function nextSequenceFor(int $classId, ?\DateTimeInterface $date = null): int
    {
        return 1 + (int) static::withoutTenant()
            ->where('class_id', $classId)
            ->whereDate('session_date', ($date ?? now())->format('Y-m-d'))
            ->max('sequence');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isOpen(): bool
    {
        return $this->status === 'open' && ! $this->isExpired();
    }

    /** Pengiriman WhatsApp gagal setelah semua percobaan. */
    public function deliveryFailed(): bool
    {
        return $this->delivery_status === 'failed';
    }

    public function verifyPin(string $pin): bool
    {
        return Hash::check($pin, $this->pin_hash);
    }

    /** URL magic link publik untuk sesi ini. */
    public function magicLink(): string
    {
        return url('/a/'.$this->token);
    }
}
