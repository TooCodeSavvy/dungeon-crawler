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
                "Current Dungeon Map:\n" .
                "Legend: [P] Player | [X] Exit | [·] Unexplored | [O] Explored | [M] Monster | [T] Treasure\n\n" .
                $map
            );
        } catch (\Exception $e) {
            return new CommandResult(false, "Failed to generate map: " . $e->getMessage());
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

    /**
     * Generates a colorized ASCII representation of the dungeon map.
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

        // Define ANSI color codes for map elements
        $colorPlayer = "\033[1;32m"; // Bold green
        $colorExit = "\033[1;36m";   // Bold cyan
        $colorMonster = "\033[1;31m"; // Bold red
        $colorTreasure = "\033[1;33m"; // Bold yellow
        $colorVisited = "\033[1;37m"; // Bold white
        $colorUnexplored = "\033[0;37m"; // Gray
        $colorAdjacent = "\033[0;36m"; // Cyan
        $colorReset = "\033[0m";      // Reset

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
                    $row .= $colorUnexplored . ' · ' . $colorReset;
                    continue;
                }

                // Determine what to display for this room with colors
                if ($position->equals($currentPosition)) {
                    $row .= $colorPlayer . '[P]' . $colorReset; // Player position
                } elseif ($room->isExit()) {
                    $row .= $colorExit . '[X]' . $colorReset; // Exit
                } elseif ($room->hasMonster() && $room->isVisited()) {
                    $row .= $colorMonster . '[M]' . $colorReset; // Monster
                } elseif ($room->hasTreasure() && $room->isVisited()) {
                    $row .= $colorTreasure . '[T]' . $colorReset; // Treasure
                } elseif ($room->isVisited()) {
                    $row .= $colorVisited . '[O]' . $colorReset; // Visited room
                } else {
                    $row .= $colorAdjacent . '[ ]' . $colorReset; // Adjacent but not visited
                }
            }
            $map .= $row . "\n";
        }

        return $map;
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