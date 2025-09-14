<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

/**
 * Command to display help information to the player.
 */
class HelpCommand implements CommandInterface
{
    /**
     * Executes the help command, displaying available commands and game instructions.
     *
     * @param ?Game $game The current game instance.
     * @return CommandResult The result of executing the command.
     */
    public function execute(?Game $game): CommandResult
    {
        $helpText = "Available Commands:\n" .
            "------------------\n" .
            "move <direction> - Move in the specified direction (north/n, south/s, east/e, west/w)\n" .
            "  Example: \"move north\" or just \"n\"\n" .
            "map - Display a map of the dungeon showing explored areas\n" .
            "inventory - View your current inventory items\n" .
            "take <item|all> - Pick up an item from the current room\n" .
            "  Example: \"take sword\" or \"take all\"\n" .
            "use <item> - Use an item from your inventory\n" .
            "  Example: \"use potion\" or \"use health potion\"\n" .
            "attack - Attack a monster in the current room (if present)\n" .
            "save - Update your current save file\n" .
            "save as - Create a new save file\n" .
            "quit - Return to the main menu\n" .
            "help - Display this help information\n
        ";

        return new CommandResult(true, $helpText);
    }

    /**
     * Determines if the help command can be executed.
     *
     * This can always be executed.
     *
     * @param ?Game $game The current game instance.
     * @return bool Always true as help is always available.
     */
    public function canExecute(?Game $game): bool
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
        return 'help';
    }
}