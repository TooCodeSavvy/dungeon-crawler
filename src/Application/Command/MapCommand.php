<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Dungeon;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\ValueObject\Position;
use DungeonCrawler\Domain\ValueObject\Direction;

/**
 * Command to display the dungeon map showing explored rooms and the player's position.
 */
class MapCommand implements CommandInterface
{
    /**
     * Executes the map command, displaying a map of the dungeon with revealed areas.
     *
     * @param ?Game $game The current game instance.
     * @return CommandResult The result of executing the command.
     */
    public function execute(?Game $game): CommandResult
    {
        if ($game === null) {
            return new CommandResult(false, "No active game to display map for.");
        }

        try {
            // Generate the map visualization as a string
            $map = $this->generateMapVisualization($game);

            // Return a result with the map as the message
            return new CommandResult(
                true,
                "Current Dungeon Map:\n" . $map
            );
        } catch (\Exception $e) {
            return new CommandResult(false, "Failed to generate map: " . $e->getMessage());
        }
    }

    /**
     * Determines if the map command can be executed.
     *
     * @param Game $game The current game instance.
     * @return bool Always true as map can be viewed anytime during gameplay.
     */
    public function canExecute(Game $game): bool
    {
        // Map can always be viewed in normal gameplay
        return true;
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

    /**
     * Generates a simple ASCII representation of the dungeon map.
     *
     * @param Game $game The current game instance.
     * @return string The ASCII map visualization.
     */
    private function generateMapVisualization(Game $game): string
    {
        $dungeon = $game->getDungeon();
        $currentPosition = $game->getCurrentPosition();
        $width = $dungeon->getWidth();
        $height = $dungeon->getHeight();

        $map = '';
        $legend = "Legend: [P] Player | [X] Exit | [·] Unexplored | [O] Explored | [M] Monster | [T] Treasure\n\n";

        // Build the map row by row
        for ($y = 0; $y < $height; $y++) {
            $row = '';
            for ($x = 0; $x < $width; $x++) {
                $position = new Position($x, $y);
                $room = $dungeon->getRoomAt($position);

                // If no room at this position
                if ($room === null) {
                    $row .= '   ';
                    continue;
                }

                // Check if the room is visible - either visited or adjacent to a visited room
                $isVisible = $room->isVisited() || $this->isAdjacentToVisitedSafe($dungeon, $position);

                if (!$isVisible) {
                    $row .= ' · ';
                    continue;
                }

                // Determine what to display for this room
                if ($position->equals($currentPosition)) {
                    $row .= '[P]'; // Player position
                } elseif ($room->isExit()) {
                    $row .= '[X]'; // Exit
                } elseif ($room->hasMonster() && $room->isVisited()) {
                    $row .= '[M]'; // Monster (only visible if room was visited)
                } elseif ($room->hasTreasure() && $room->isVisited()) {
                    $row .= '[T]'; // Treasure (only visible if room was visited)
                } elseif ($room->isVisited()) {
                    $row .= '[O]'; // Visited room
                } else {
                    $row .= '[ ]'; // Adjacent but not visited
                }
            }
            $map .= $row . "\n";
        }

        return $legend . $map;
    }

    /**
     * Safely checks if a position is adjacent to any visited room, preventing exceptions
     * from negative coordinates or other boundary issues.
     *
     * @param Dungeon $dungeon The dungeon instance.
     * @param Position $position The position to check.
     * @return bool True if the position is adjacent to a visited room.
     */
    private function isAdjacentToVisitedSafe(
        Dungeon $dungeon,
        Position $position
    ): bool {
        // Check all adjacent positions
        $directions = Direction::cases();

        foreach ($directions as $direction) {
            try {
                $adjacentPosition = $position->move($direction);

                // Get the room at the adjacent position
                $adjacentRoom = $dungeon->getRoomAt($adjacentPosition);

                // Skip if no room at this adjacent position
                if ($adjacentRoom === null) {
                    continue;
                }

                if ($adjacentRoom->isVisited()) {
                    return true;
                }
            } catch (\InvalidArgumentException $e) {
                // If we get an exception (like negative coordinates), just skip this direction
                continue;
            }
        }

        return false;
    }
}