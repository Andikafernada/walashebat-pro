<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Laporan izin/sakit dari orang tua lewat formulir publik. */
class StudentExcuse extends Model
{
    use BelongsToTenant;

    /*
     * user_id SENGAJA tidak masuk sini, sama seperti CharacterReflection:
     * kepemilikan tidak boleh bisa dititipkan lewat request dari formulir
     * publik. Diisi lewat forceFill() di controller.
     */
    protected $fillable = [
        'class_id', 'student_id', 'tanggal', 'jenis', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }
}
