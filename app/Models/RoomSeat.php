<?php

namespace App\Models;

use App\Enums\RoomSeatStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomSeat extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'room_id',
        'user_id',
        'seat_number',
        'status',
        'ready_at',
        'joined_at',
        'left_at',
        'reservation_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => RoomSeatStatus::class,
            'seat_number' => 'integer',
            'ready_at' => 'datetime',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<WalletReservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(WalletReservation::class);
    }
}
