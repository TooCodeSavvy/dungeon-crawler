<?php
declare(strict_types=1);
namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

/**
 * Command to display the dungeon map showing explored rooms and the player's position.
 */
class MapCommand implements CommandInterface
{
    /**
     * Executes the map command, returning map content for the game engine to render.
     *
     * @param ?Game $game The current game instance.
     * @return CommandResult The result of executing the command.
     */
    public function execute(?Game $game): CommandResult
    {
        if ($game === null) {
            return CommandResult::failure("No active game to display map for.");
        }

        try {
            // Return success result with map flag
            return CommandResult::success("", ['showFullMap' => true]);
        } catch (\Exception $e) {
            return CommandResult::failure("Failed to generate map: " . $e->getMessage());
        }
    }

    /**
     * Determines if the map command can be executed.
     *
     * @param ?Game $game The current game instance.
     * @return bool Always true as map can be viewed anytime during gameplay.
     */
    public function canExecute(?Game $game): bool
    {
        // Map can always be viewed as long as there's a game
        return $game !== null;
    }

    /**
     * Gets the name of the command.
     *
     * @return string The command name.
     */
    public function getName(): string
    {
        return 'map';
    }
}