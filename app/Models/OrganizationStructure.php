<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationStructure extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'class_id', 'student_id', 'role', 'sort_order',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function roleLabel(): string
    {
        return config("walikelas.student_roles.{$this->role}", $this->role);
    }
}
