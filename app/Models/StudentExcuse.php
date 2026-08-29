<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Laporan izin/sakit dari orang tua lewat formulir publik. */
class StudentExcuse extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'class_id', 'student_id', 'tanggal', 'jenis', 'keterangan', 'attachment_path', 'parent_phone_verified',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'parent_phone_verified' => 'boolean',
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

    /** URL foto bukti / surat izin jika ada */
    public function attachmentUrl(): ?string
    {
        return $this->attachment_path ? asset('storage/' . $this->attachment_path) : null;
    }
}
