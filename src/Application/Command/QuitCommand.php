<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

/**
 * Command to quit the current game.
 *
 * When executed, this command signals the game engine to terminate the game loop
 * and exit the application gracefully.
 */
class QuitCommand implements CommandInterface
{
    /**
     * Executes the quit command.
     *
     * Returns a successful CommandResult indicating the game should quit.
     *
     * @param Game $game The current active game instance.
     * @return CommandResult The result indicating quitting the game.
     */
    public function execute(Game $game): CommandResult
    {
        // Signal to quit the game (e.g., stop the game loop)
        return new CommandResult(true, "Quitting game...", true);
    }

    /**
     * Determines if quitting is currently allowed.
     *
     * For now, quitting is always allowed.
     *
     * @param Game $game The current game instance.
     * @return bool True if the quit command can be executed.
     */
    public function canExecute(Game $game): bool
    {
        return true;
    }

    /**
     * Returns the internal command name.
     *
     * @return string Command identifier 'quit'.
     */
    public function getName(): string
    {
        return 'quit';
    }
}
