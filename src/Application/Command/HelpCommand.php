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
        $helpText = <<<EOT
DUNGEON CRAWLER - COMMAND REFERENCE

MOVEMENT:
- north, n       Move north
- south, s       Move south
- east, e        Move east
- west, w        Move west
- move <dir>     Alternative movement command

COMBAT:
- attack         Attack a monster 
- flee           Try to escape from combat

ITEMS:
- take           Take all items in room
- take <item>    Take specific item
- use <item>     Use potion or equip weapon
- equip <weapon> Equip a weapon directly

INFO:
- map, m         Show dungeon map
- inventory, i   Check your inventory 

GAME:
- save           Save current game 
- quit, q        Exit game
- help, h        Show this help

TIPS:
• Find better weapons to increase attack power
• Use health potions when injured
• Save your game often
• Defeat monsters blocking your path
• Find the exit to win!
EOT;

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