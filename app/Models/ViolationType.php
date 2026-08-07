<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationType extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'name', 'category', 'points'];

    protected function casts(): array
    {
        return ['points' => 'integer'];
    }

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }
}
