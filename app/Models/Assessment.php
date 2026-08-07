<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu kesempatan menilai: satu Capaian Pembelajaran, satu tanggal.
 *
 * Sejajar dengan AttendanceSession pada presensi — wadahnya di sini, nilai
 * tiap siswanya di AssessmentScore.
 */
class Assessment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id', 'class_id', 'mapel', 'capaian_pembelajaran', 'assessment_date',
    ];

    protected function casts(): array
    {
        return ['assessment_date' => 'date'];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    /**
     * Rata-rata nilai, mengabaikan yang belum dinilai.
     *
     * Siswa tanpa nilai TIDAK dihitung sebagai nol. Kalau ikut dihitung,
     * rata-rata kelas anjlok oleh anak yang sebenarnya belum diuji — dan guru
     * mengejar remedial untuk siswa yang tidak memerlukannya.
     */
    public function rataRata(): ?float
    {
        $terisi = $this->scores->whereNotNull('nilai');

        return $terisi->isEmpty() ? null : round($terisi->avg('nilai'), 1);
    }

    /** Berapa siswa yang nilainya belum diisi. */
    public function belumDinilai(): int
    {
        return $this->scores->whereNull('nilai')->count();
    }
}
