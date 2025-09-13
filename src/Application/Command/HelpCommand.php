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
     * Executes the help command, displaying available commands and their usage.
     *
     * @param ?Game $game The current game instance.
     * @return CommandResult The result of executing the command.
     */
    public function execute(?Game $game): CommandResult
    {
        $helpText = <<<HELP
Available Commands:
------------------
move <direction> - Move in the specified direction (north/n, south/s, east/e, west/w)
  Example: "move north" or just "n"

map - Display a map of the dungeon showing explored areas

inventory - View your current inventory items

take <item|all> - Pick up an item from the current room
  Example: "take sword" or "take all"

attack - Attack a monster in the current room (if present)

save - Save your current game progress

quit - Return to the main menu

help - Display this help information

Tips:
----
• Explore the dungeon to find the exit
• Collect treasures to increase your score
• Defeat monsters to earn experience
• Watch your health - you can't continue if you die!
HELP;

        return new CommandResult(true, $helpText);
    }

    /**
     * Determines if the help command can be executed.
     *
     * This can always be executed.
     *
     * @param Game $game The current game instance.
     * @return bool Always true as help is always available.
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
        return 'help';
    }
}