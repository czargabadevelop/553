<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomSetting extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'room_id',
        'arrangement_timeout_seconds',
        'turn_timeout_seconds',
        'auto_start',
        'restrictions',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'arrangement_timeout_seconds' => 'integer',
            'turn_timeout_seconds' => 'integer',
            'auto_start' => 'boolean',
            'restrictions' => 'array',
        ];
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
