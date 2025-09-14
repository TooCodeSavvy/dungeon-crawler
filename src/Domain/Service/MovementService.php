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

    /**
     * Gets an array of available directions from a room.
     *
     * @param Room $room The room to check
     * @return Direction[] Array of available directions
     */
    private function getAvailableDirections(Room $room): array
    {
        return $room->getAvailableDirections();
    }

    /**
     * Provides a detailed look at what's in each direction.
     *
     * @param Player $player The player
     * @param Dungeon $dungeon The current dungeon
     *
     * @return array<string, string> Map of direction names to descriptions
     */
    public function scout(Player $player, Dungeon $dungeon): array
    {
        $currentRoom = $dungeon->getRoomAt($player->getPosition());

        if ($currentRoom === null) {
            return ['error' => 'You are not in a valid room!'];
        }

        $scoutInfo = [];

        foreach (Direction::cases() as $direction) {
            if ($currentRoom->hasConnection($direction)) {
                $targetRoom = $dungeon->getRoomInDirection($currentRoom->getPosition(), $direction);

                if ($targetRoom === null) {
                    $scoutInfo[$direction->value] = 'A solid wall blocks the way.';
                    continue;
                }

                if ($targetRoom->isVisited()) {
                    // Provide more info for visited rooms
                    if ($targetRoom->isExit()) {
                        $scoutInfo[$direction->value] = '✨ The EXIT is here!';
                    } elseif ($targetRoom->hasMonster()) {
                        $monsterName = $targetRoom->getMonster()->getName();
                        $health = $targetRoom->getMonster()->getHealth();
                        $scoutInfo[$direction->value] = sprintf(
                            '⚔️ %s (%d/%d HP)',
                            $monsterName,
                            $health->getValue(),
                            $health->getMax()
                        );
                    } elseif ($targetRoom->hasTreasure()) {
                        $scoutInfo[$direction->value] = '💰 Treasure awaits!';
                    } else {
                        $scoutInfo[$direction->value] = 'An empty room you\'ve visited before.';
                    }
                } else {
                    // Unvisited rooms show minimal info
                    $scoutInfo[$direction->value] = 'An unexplored room.';
                }
            } else {
                $scoutInfo[$direction->value] = 'A solid wall blocks the way.';
            }
        }

        return $scoutInfo;
    }

    /**
     * Checks if a player can move in a specific direction.
     *
     * @param Player $player The player
     * @param Direction $direction The direction to check
     * @param Dungeon $dungeon The current dungeon
     *
     * @return bool True if movement is possible
     */
    public function canMove(Player $player, Direction $direction, Dungeon $dungeon): bool
    {
        $currentPosition = $player->getPosition();
        $currentRoom = $dungeon->getRoomAt($currentPosition);

        if ($currentRoom === null) {
            return false;
        }

        if (!$currentRoom->hasConnection($direction)) {
            return false;
        }

        try {
            $newPosition = $currentPosition->move($direction);
            return $dungeon->getRoomAt($newPosition) !== null;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Generates a message when hitting a wall.
     */
    private function getWallMessage(Direction $direction): string
    {
        $messages = [
            'You bump into a solid wall.',
            'There\'s no passage in that direction.',
            'A wall blocks your path.',
            'You cannot go that way.',
            sprintf('The %s wall is solid stone.', $direction->value),
        ];

        return $messages[array_rand($messages)];
    }

    /**
     * Builds a message describing the movement.
     */
    private function buildMovementMessage(Direction $direction, Room $newRoom): string
    {
        $base = sprintf('You move %s.', $direction->value);

        if (!$newRoom->isVisited()) {
            $base .= ' This is a new area!';
        }

        if ($newRoom->isExit()) {
            $base .= ' 🎉 You\'ve found the exit!';
        } elseif ($newRoom->hasMonster()) {
            $monster = $newRoom->getMonster();
            $base .= sprintf(' ⚠️ A %s appears!', $monster->getName());
        } elseif ($newRoom->hasTreasure()) {
            $base .= ' ✨ You spot something valuable!';
        }

        return $base;
    }

    /**
     * Builds a detailed description of the room.
     */
    private function buildRoomDescription(Room $room, Dungeon $dungeon): string
    {
        $description = $room->getDescription();

        // Add information about room contents
        $contents = [];

        if ($room->hasMonster()) {
            $monster = $room->getMonster();
            $contents[] = sprintf(
                'A %s stands before you! (Health: %d/%d)',
                $monster->getName(),
                $monster->getHealth()->getValue(),
                $monster->getHealth()->getMax()
            );
        }

        if ($room->hasTreasure()) {
            $treasure = $room->getTreasure();
            $contents[] = sprintf(
                'You see %s glinting in the corner.',
                $treasure->getName()
            );
        }

        if ($room->isExit()) {
            $contents[] = 'The exit glows with inviting light. Your escape is at hand!';
        }

        if (!empty($contents)) {
            $description .= "\n\n" . implode("\n", $contents);
        }

        return $description;
    }
}