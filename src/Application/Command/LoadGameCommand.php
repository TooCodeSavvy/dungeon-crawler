<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

class LoadGameCommand implements CommandInterface
{
    private string $saveId;

    public function __construct(string $saveId)
    {
        $this->saveId = $saveId;
    }

    public function execute(Game $game): CommandResult
    {
        // Implement loading logic here.

        // Example:
        // $success = $game->loadSave($this->saveId);
        // if ($success) { ... }

        return new CommandResult(true, "Game loaded from save '{$this->saveId}'.");
    }

    public function canExecute(Game $game): bool
    {
        return true; // or check if save exists
    }

    public function getName(): string
    {
        return 'load_game';
    }
}
