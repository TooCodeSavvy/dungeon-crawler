<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

interface CommandInterface
{
    /**
     * Execute the command
     */
    public function execute(Game $game): CommandResult;

    /**
     * Check if the command can be executed in the current game state
     */
    public function canExecute(Game $game): bool;

    /**
     * Get the command name for logging/debugging
     */
    public function getName(): string;
}