<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Service;

use DungeonCrawler\Domain\Entity\Dungeon;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Entity\Player;
use DungeonCrawler\Domain\Entity\Room;
use DungeonCrawler\Domain\ValueObject\Direction;
use DungeonCrawler\Domain\ValueObject\LocationInfo;
use DungeonCrawler\Domain\ValueObject\MovementResult;

/**
 * Service responsible for handling player movement within the dungeon.
 *
 * This service encapsulates all movement logic including validation,
 * room transitions, and movement-related events.
 */
class MovementService
{
    /**
     * Attempts to move a player in the specified direction.
     *
     * @param Game $game The current game instance
     * @param Direction $direction The direction to move in
     *
     * @return MovementResult The result of the movement attempt
     */
    public function move(Game $game, Direction $direction): MovementResult
    {
        $dungeon = $game->getDungeon();
        $currentPosition = $game->getCurrentPosition();
        $currentRoom = $game->getCurrentRoom();

        // Check if there's a connection in that direction
        if (!$currentRoom->hasConnection($direction)) {
            return MovementResult::failure("You can't go {$direction->value} from here. There's a wall.");
        }

        try {
            $newPosition = $currentPosition->move($direction);
            $newRoom = $dungeon->getRoomAt($newPosition);

            if ($newRoom === null) {
                return MovementResult::failure("You can't go {$direction->value} from here. There's nothing there.");
            }

            // Check if there's a monster blocking the way
            if ($newRoom->hasMonster()) {
                $monster = $newRoom->getMonster();
                $game->setBlockingMonster($monster, $direction);
                return MovementResult::blocked(
                    "A {$monster->getName()} blocks your path! You can still move in other directions, or fight the monster.",
                    $monster
                );
            }

            // Move is successful - update player position
            $game->movePlayer($newPosition);

            // Create location info for the result
            $locationInfo = new LocationInfo(
                $newRoom->getDescription(),
                $newRoom->hasTreasure(),
                $newRoom->isExit(),
                $newRoom->getAvailableDirections()
            );

            return MovementResult::success($locationInfo);

        } catch (\InvalidArgumentException $e) {
            // This would happen if the new position is invalid (e.g., negative coordinates)
            return MovementResult::failure("You can't go {$direction->value} from here. You've reached the edge of the dungeon.");
        }
    }
}