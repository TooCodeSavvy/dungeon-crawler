<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

/**
 * Interface for commands that can be executed within the game.
 */
interface CommandInterface
{
    /**
     * Executes the command logic.
     *
     * @param ?Game $game The current game instance.
     * @return CommandResult The result of command execution.
     */
    public function execute(?Game $game): CommandResult;

    /**
     * Determines if the command is currently allowed to be executed.
     *
     * @param ?Game $game The current game instance.
     * @return bool True if the command can be executed, false otherwise.
     */
    public function canExecute(?Game $game): bool;

    /**
     * Gets the name of the command.
     *
     * @return string The command name.
     */
    public function getName(): string;
}
