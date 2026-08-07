<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'attendance_session_id', 'student_id', 'status', 'note',
    ];

    public const STATUSES = ['hadir', 'terlambat', 'sakit', 'izin', 'alfa'];

    /**
     * Status yang berarti siswa BERADA di sekolah.
     *
     * Terlambat termasuk hadir untuk perhitungan persentase kehadiran —
     * anaknya masuk, hanya tidak tepat waktu. Keterlambatannya tetap dihitung
     * terpisah sebagai urusan kedisiplinan, bukan ketidakhadiran.
     */
    public const STATUS_MASUK = ['hadir', 'terlambat'];

    /** Label singkat untuk kolom tabel rekap: H, T, S, I, A. */
    public const KODE = [
        'hadir' => 'H',
        'terlambat' => 'T',
        'sakit' => 'S',
        'izin' => 'I',
        'alfa' => 'A',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    /** Riwayat koreksi, terbaru lebih dulu. */
    public function revisions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttendanceRevision::class)->latest('id');
    }

    /** Apakah baris ini pernah dikoreksi setelah dikirim petugas? */
    public function wasCorrected(): bool
    {
        // Gunakan revisions_count jika sudah di-load, atau query ulang jika tidak
        if (isset($this->revisions_count)) {
            return $this->revisions_count > 0;
        }
        return $this->revisions()->exists();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
