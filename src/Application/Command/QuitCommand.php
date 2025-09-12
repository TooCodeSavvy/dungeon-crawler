<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

class QuitCommand implements CommandInterface
{
    public function execute(Game $game): CommandResult
    {
        // Signal game quit
        return new CommandResult(true, "Quitting game...", true);
    }

    public function canExecute(Game $game): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'quit';
    }
}
