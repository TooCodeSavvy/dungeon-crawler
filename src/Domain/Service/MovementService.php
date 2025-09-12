<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Service;

use DungeonCrawler\Domain\Entity\Dungeon;
use DungeonCrawler\Domain\Entity\Player;
use DungeonCrawler\Domain\Entity\Room;
use DungeonCrawler\Domain\ValueObject\Direction;
use DungeonCrawler\Domain\ValueObject\Position;

/**
 * Service responsible for handling player movement within the dungeon.
 *
 * This service encapsulates all movement logic including validation,
 * room transitions, and movement-related events.
 */
final class MovementService
{
    /**
     * Attempts to move the player in the specified direction.
     *
     * @param Player $player The player attempting to move
     * @param Direction $direction The direction to move
     * @param Dungeon $dungeon The current dungeon
     *
     * @return MovementResult The result of the movement attempt
     */
    public function move(Player $player, Direction $direction, Dungeon $dungeon): MovementResult
    {
        $currentPosition = $player->getPosition();
        $currentRoom = $dungeon->getRoomAt($currentPosition);

        if ($currentRoom === null) {
            return MovementResult::failed('You are not in a valid room!');
        }

        // Check if there's a monster blocking the way
        if ($currentRoom->hasMonster()) {
            return MovementResult::blocked(
                'You cannot leave! A ' . $currentRoom->getMonster()->getName() . ' blocks your path!'
            );
        }

        // Check if the current room has a connection in that direction
        if (!$currentRoom->hasConnection($direction)) {
            return MovementResult::failed($this->getWallMessage($direction));
        }

        // Check if there's actually a room in that direction
        $targetRoom = $dungeon->getRoomInDirection($currentPosition, $direction);
        if ($targetRoom === null) {
            return MovementResult::failed($this->getWallMessage($direction));
        }

        // Calculate new position
        try {
            $newPosition = $currentPosition->move($direction);
        } catch (\InvalidArgumentException $e) {
            return MovementResult::failed('You cannot move in that direction!');
        }

        // Verify the new position has a room (double-check)
        $newRoom = $dungeon->getRoomAt($newPosition);
        if ($newRoom === null) {
            return MovementResult::failed($this->getWallMessage($direction));
        }

        // Execute the movement
        $player->moveTo($newPosition);
        $newRoom->enter();

        // Update the room in the dungeon to persist the visited state
        $dungeon->updateRoom($newRoom);

        // Build the result with room information
        return MovementResult::success(
            room: $newRoom,
            message: $this->buildMovementMessage($direction, $newRoom),
            description: $this->buildRoomDescription($newRoom, $dungeon)
        );
    }

    /**
     * Gets available movement options from the player's current position.
     *
     * @param Player $player The player
     * @param Dungeon $dungeon The current dungeon
     *
     * @return array<Direction> Array of available directions
     */
    public function getAvailableDirections(Player $player, Dungeon $dungeon): array
    {
        $currentRoom = $dungeon->getRoomAt($player->getPosition());

        if ($currentRoom === null) {
            return [];
        }

        // If there's a monster, no directions are available
        if ($currentRoom->hasMonster()) {
            return [];
        }

        $availableDirections = [];

        foreach ($currentRoom->getAvailableDirections() as $direction) {
            // Verify there's actually a room in that direction
            $targetRoom = $dungeon->getRoomInDirection($currentRoom->getPosition(), $direction);
            if ($targetRoom !== null) {
                $availableDirections[] = $direction;
            }
        }

        return $availableDirections;
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
     * Checks if the player can move (not blocked by monster).
     *
     * @param Player $player The player
     * @param Dungeon $dungeon The current dungeon
     *
     * @return bool True if movement is possible
     */
    public function canMove(Player $player, Dungeon $dungeon): bool
    {
        $currentRoom = $dungeon->getRoomAt($player->getPosition());

        if ($currentRoom === null) {
            return false;
        }

        // Cannot move if there's a living monster in the room
        return !$currentRoom->hasMonster();
    }

    /**
     * Gets information about the player's current location.
     *
     * @param Player $player The player
     * @param Dungeon $dungeon The current dungeon
     *
     * @return LocationInfo Information about the current location
     */
    public function getCurrentLocationInfo(Player $player, Dungeon $dungeon): LocationInfo
    {
        $currentRoom = $dungeon->getRoomAt($player->getPosition());

        if ($currentRoom === null) {
            return new LocationInfo(
                position: $player->getPosition(),
                description: 'You are lost in the void!',
                hasMonster: false,
                hasTreasure: false,
                isExit: false,
                availableDirections: []
            );
        }

        return new LocationInfo(
            position: $player->getPosition(),
            description: $currentRoom->getDescription(),
            hasMonster: $currentRoom->hasMonster(),
            hasTreasure: $currentRoom->hasTreasure(),
            isExit: $currentRoom->isExit(),
            availableDirections: $this->getAvailableDirections($player, $dungeon),
            room: $currentRoom
        );
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