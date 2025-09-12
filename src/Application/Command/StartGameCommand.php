<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

class StartGameCommand implements CommandInterface
{
    private string $playerName;
    private string $difficulty;

    public function __construct(string $playerName, string $difficulty)
    {
        $this->playerName = $playerName;
        $this->difficulty = $difficulty;
    }

    public function execute(Game $game): CommandResult
    {
        // Here, implement logic to initialize a new game.
        // For now, just a placeholder returning success.

        // Example:
        // $game->startNewGame($this->playerName, $this->difficulty);

        return new CommandResult(true, "New game started for {$this->playerName}.");
    }

    public function canExecute(Game $game): bool
    {
        // You can add conditions to check if this command can run now
        return true;
    }

    public function getName(): string
    {
        return 'start_game';
    }
}
