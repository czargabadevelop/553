<?php

namespace App\Domain\Audit;

use App\Models\AuditLog;
use App\Models\Game;
use App\Models\GameAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Record an administrative or financial action.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function log(
        string $action,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        ?User $actor = null,
        ?string $actorRole = null,
    ): AuditLog {
        $actor ??= auth()->user();

        return AuditLog::create([
            'actor_id' => $actor?->id,
            'actor_role' => $actorRole ?? $actor?->role,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => $this->ipAddress(),
            'user_agent' => $this->userAgent(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function game(Game $game, string $action, array $metadata = [], ?User $actor = null): GameAuditLog
    {
        return GameAuditLog::create([
            'game_id' => $game->id,
            'user_id' => $actor?->id,
            'action' => $action,
            'actor' => $actor ? 'player' : 'system',
            'metadata' => $metadata ?: null,
        ]);
    }

    private function ipAddress(): ?string
    {
        return app()->runningInConsole() ? null : request()->ip();
    }

    private function userAgent(): ?string
    {
        return app()->runningInConsole() ? null : substr((string) request()->userAgent(), 0, 255);
    }
}
