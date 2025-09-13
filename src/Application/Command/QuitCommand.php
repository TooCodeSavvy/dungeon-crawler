<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Application\State\MenuState;

/**
 * Command to quit the current game and return to the main menu.
 */
class QuitCommand implements CommandInterface
{
    /**
     * Executes the quit command, ending the current game session.
     *
     * @param ?Game $game The current game instance.
     * @return CommandResult The result of executing the command.
     */
    public function execute(?Game $game): CommandResult
    {
        // Return a result that requires a state transition to the menu
        return new CommandResult(
            true,
            "Returning to main menu...",
            ['quit' => true]
        );
    }

    /**
     * Determines if the quit command can be executed.
     *
     * This can always be executed.
     *
     * @param Game $game The current game instance.
     * @return bool Always true as quitting is always available.
     */
    public function canExecute(Game $game): bool
    {
        return true;
    }

    /**
     * Gets the name of the command.
     *
     * @return string The command name.
     */
    public function getName(): string
    {
        return 'quit';
    }
}