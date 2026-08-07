<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nilai satu siswa pada satu penilaian.
 *
 * `nilai` boleh NULL dan artinya berbeda dari nol: kosong berarti belum
 * dinilai, nol berarti dinilai dan hasilnya nol. Perbedaan itu dijaga sampai
 * ke perhitungan rata-rata.
 */
class AssessmentScore extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id', 'assessment_id', 'student_id', 'nilai', 'catatan',
    ];

    protected function casts(): array
    {
        return ['nilai' => 'integer'];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
