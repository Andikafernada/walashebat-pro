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

    /** Nilai sehari-hari per Capaian Pembelajaran — ranah guru mapel. */
    public const JENIS_HARIAN = 'harian';

    /** Penilaian Tengah Semester (PTS/STS). */
    public const JENIS_PTS = 'pts';

    /** Penilaian Akhir Semester (PAS/SAS). */
    public const JENIS_PAS = 'pas';

    /** @return array<string, string> jenis => label yang dibaca guru */
    public static function jenisTersedia(): array
    {
        return [
            self::JENIS_HARIAN => 'Nilai Harian',
            self::JENIS_PTS => 'Tengah Semester (PTS)',
            self::JENIS_PAS => 'Akhir Semester (PAS)',
        ];
    }

    protected $fillable = [
        'user_id', 'class_id', 'jenis', 'semester', 'mapel',
        'capaian_pembelajaran', 'assessment_date',
    ];

    protected $attributes = [
        'jenis' => self::JENIS_HARIAN,
    ];

    protected function casts(): array
    {
        return [
            'assessment_date' => 'date',
            'semester' => 'integer',
        ];
    }

    /** Penilaian rapor: yang direkap wali kelas, bukan nilai sehari-hari. */
    public function scopeRapor($query)
    {
        return $query->whereIn('jenis', [self::JENIS_PTS, self::JENIS_PAS]);
    }

    public function scopeSemester($query, int $semester)
    {
        return $query->where('semester', $semester);
    }

    public function labelJenis(): string
    {
        return self::jenisTersedia()[$this->jenis] ?? $this->jenis;
    }

    /** Nilai harian butuh Capaian Pembelajaran; PTS dan PAS tidak. */
    public function harian(): bool
    {
        return $this->jenis === self::JENIS_HARIAN;
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
