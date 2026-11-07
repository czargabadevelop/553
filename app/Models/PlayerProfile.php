<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerProfile extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'display_name',
        'date_of_birth',
        'country_code',
        'phone',
        'timezone',
        'avatar_path',
        'self_excluded_at',
        'deposit_limit_minor',
        'session_limit_minutes',
        'last_seen_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'self_excluded_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'deposit_limit_minor' => 'integer',
            'session_limit_minutes' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSelfExcluded(): bool
    {
        return $this->self_excluded_at !== null;
    }

    /**
     * Age in whole years, or null when no date of birth is recorded.
     */
    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }
}
