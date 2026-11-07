<?php

namespace App\Console\Commands;

use App\Domain\Game\GameService;
use App\Enums\GameStatus;
use App\Models\Game;
use Illuminate\Console\Command;
use Throwable;

/**
 * Backstop for the arrangement timer. Players normally trigger resolution
 * from the table the moment their countdown ends; this sweep catches rounds
 * where every client has closed or lost its connection.
 */
class ResolveExpiredGames extends Command
{
    protected $signature = 'games:resolve-expired';

    protected $description = 'Auto-foul players who missed the arrangement deadline and evaluate the round';

    public function handle(GameService $games): int
    {
        $resolved = 0;

        Game::query()
            ->where('status', GameStatus::Arranging->value)
            ->whereNotNull('arrangement_deadline_at')
            ->where('arrangement_deadline_at', '<=', now())
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->each(function (Game $game) use ($games, &$resolved) {
                try {
                    if ($games->resolveDeadline($game) !== null) {
                        $resolved++;
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $this->warn("Game {$game->code} could not be resolved: {$exception->getMessage()}");
                }
            });

        $this->info("Resolved {$resolved} expired game(s).");

        return self::SUCCESS;
    }
}
