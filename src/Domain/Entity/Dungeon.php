<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Entity;

use DungeonCrawler\Domain\ValueObject\Direction;
use DungeonCrawler\Domain\ValueObject\Position;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Represents the complete dungeon structure containing interconnected rooms.
 *
 * The Dungeon entity is the aggregate root for managing the collection of rooms
 * and their relationships. It provides navigation methods and maintains the
 * overall dungeon topology including entrance and exit positions.
 */
class Dungeon
{
    private UuidInterface $id;

    /**
     * @var array<string, Room> Map of position strings to Room entities
     */
    private array $rooms;

    /**
     * @param array<string, Room> $rooms Collection of rooms indexed by position string
     * @param Position $entrancePosition Starting position for players
     * @param Position $exitPosition Victory condition position
     * @param int $width Dungeon width in rooms
     * @param int $height Dungeon height in rooms
     * @param int $difficulty Difficulty level affecting monster/treasure generation
     * @param UuidInterface|null $id Optional UUID for persistence
     *
     * @throws \InvalidArgumentException If rooms array is empty
     * @throws \InvalidArgumentException If dimensions are invalid
     */
    public function __construct(
        array $rooms,
        private readonly Position $entrancePosition,
        private readonly Position $exitPosition,
        private readonly int $width,
        private readonly int $height,
        private readonly int $difficulty,
        ?UuidInterface $id = null
    ) {
        if (empty($rooms)) {
            throw new \InvalidArgumentException('Dungeon must have at least one room');
        }

        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException('Dungeon dimensions must be positive');
        }

        if ($difficulty < 1) {
            throw new \InvalidArgumentException('Difficulty must be at least 1');
        }

        $this->id = $id ?? Uuid::uuid4();
        $this->rooms = $rooms;

        $this->validateStructure();
    }

    /**
     * Validates that entrance and exit rooms exist in the dungeon.
     *
     * @throws \RuntimeException If entrance or exit room is missing
     */
    private function validateStructure(): void
    {
        if (!isset($this->rooms[$this->entrancePosition->toString()])) {
            throw new \RuntimeException('Entrance room not found in dungeon');
        }

        if (!isset($this->rooms[$this->exitPosition->toString()])) {
            throw new \RuntimeException('Exit room not found in dungeon');
        }
    }

    /**
     * Retrieves a room at the specified position.
     *
     * @param Position $position The position to check
     * @return Room|null The room at the position, or null if none exists
     */
    public function getRoomAt(Position $position): ?Room
    {
        return $this->rooms[$position->toString()] ?? null;
    }

    /**
     * Gets the entrance room where players start.
     *
     * @return Room The entrance room
     * @throws \RuntimeException If entrance room not found (should never happen after validation)
     */
    public function getEntrance(): Room
    {
        $room = $this->getRoomAt($this->entrancePosition);
        if ($room === null) {
            throw new \RuntimeException('Entrance room not found');
        }
        return $room;
    }

    /**
     * Gets the exit room representing the victory condition.
     *
     * @return Room The exit room
     * @throws \RuntimeException If exit room not found (should never happen after validation)
     */
    public function getExit(): Room
    {
        $room = $this->getRoomAt($this->exitPosition);
        if ($room === null) {
            throw new \RuntimeException('Exit room not found');
        }
        return $room;
    }

    /**
     * Finds a room in the specified direction from the current position.
     *
     * @param Position $currentPosition The starting position
     * @param Direction $direction The direction to check
     * @return Room|null The room in that direction, or null if none exists or out of bounds
     */
    public function getRoomInDirection(Position $currentPosition, Direction $direction): ?Room
    {
        try {
            $newPosition = $currentPosition->move($direction);
            return $this->getRoomAt($newPosition);
        } catch (\InvalidArgumentException $e) {
            // Position would be out of bounds
            return null;
        }
    }

    /**
     * Checks if movement in a direction is valid from the current position.
     *
     * @param Position $currentPosition The starting position
     * @param Direction $direction The direction to check
     * @return bool True if movement is possible
     */
    public function canMove(Position $currentPosition, Direction $direction): bool
    {
        $currentRoom = $this->getRoomAt($currentPosition);
        if ($currentRoom === null) {
            return false;
        }

        // Check if the current room has a connection in that direction
        if (!$currentRoom->hasConnection($direction)) {
            return false;
        }

        // Check if there's actually a room in that direction
        return $this->getRoomInDirection($currentPosition, $direction) !== null;
    }

    /**
     * Updates a room in the dungeon (used after combat, treasure collection, etc).
     *
     * @param Room $room The updated room to store
     */
    public function updateRoom(Room $room): void
    {
        $this->rooms[$room->getPosition()->toString()] = $room;
    }

    /**
     * Gets all rooms in the dungeon.
     *
     * @return array<string, Room> All rooms indexed by position string
     */
    public function getAllRooms(): array
    {
        return $this->rooms;
    }

    /**
     * Counts the number of visited rooms.
     *
     * @return int Number of rooms that have been visited
     */
    public function getVisitedRoomCount(): int
    {
        return count(array_filter(
            $this->rooms,
            fn(Room $room) => $room->isVisited()
        ));
    }

    /**
     * Gets statistics about the dungeon for display or scoring.
     *
     * @return array{
     *     total_rooms: int,
     *     visited_rooms: int,
     *     rooms_with_monsters: int,
     *     rooms_with_treasure: int,
     *     empty_rooms: int,
     *     difficulty: int,
     *     size: string
     * }
     */
    public function getStatistics(): array
    {
        $totalRooms = count($this->rooms);
        $visitedRooms = $this->getVisitedRoomCount();
        $roomsWithMonsters = 0;
        $roomsWithTreasure = 0;
        $emptyRooms = 0;

        foreach ($this->rooms as $room) {
            if ($room->hasMonster()) {
                $roomsWithMonsters++;
            }
            if ($room->hasTreasure()) {
                $roomsWithTreasure++;
            }
            if ($room->isEmpty() && !$room->isExit()) {
                $emptyRooms++;
            }
        }

        return [
            'total_rooms' => $totalRooms,
            'visited_rooms' => $visitedRooms,
            'rooms_with_monsters' => $roomsWithMonsters,
            'rooms_with_treasure' => $roomsWithTreasure,
            'empty_rooms' => $emptyRooms,
            'difficulty' => $this->difficulty,
            'size' => sprintf('%dx%d', $this->width, $this->height),
        ];
    }

    /**
     * Generates an ASCII map of the dungeon for display.
     *
     * @param Position|null $playerPosition Current player position to highlight
     * @return string ASCII representation of the dungeon
     */
    public function getAsciiMap(?Position $playerPosition = null): string
    {
        $map = [];

        for ($y = 0; $y < $this->height; $y++) {
            $row = [];
            for ($x = 0; $x < $this->width; $x++) {
                $position = new Position($x, $y);
                $room = $this->getRoomAt($position);

                if ($room === null) {
                    $row[] = '   '; // Empty space
                } elseif ($playerPosition && $position->equals($playerPosition)) {
                    $row[] = '[P]'; // Player position
                } elseif ($position->equals($this->exitPosition)) {
                    $row[] = '[E]'; // Exit
                } elseif ($position->equals($this->entrancePosition)) {
                    $row[] = '[S]'; // Start
                } elseif ($room->isVisited()) {
                    if ($room->hasMonster()) {
                        $row[] = '[M]'; // Visited room with monster
                    } elseif ($room->hasTreasure()) {
                        $row[] = '[T]'; // Visited room with treasure
                    } else {
                        $row[] = '[.]'; // Visited empty room
                    }
                } else {
                    $row[] = '[?]'; // Unvisited room
                }
            }
            $map[] = implode(' ', $row);
        }

        return implode("\n", $map);
    }

    // Getters

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getDifficulty(): int
    {
        return $this->difficulty;
    }

    public function getEntrancePosition(): Position
    {
        return $this->entrancePosition;
    }

    public function getExitPosition(): Position
    {
        return $this->exitPosition;
    }

    /**
     * Checks if the player has reached the exit.
     *
     * @param Position $playerPosition Current player position
     * @return bool True if player is at the exit
     */
    public function isPlayerAtExit(Position $playerPosition): bool
    {
        return $playerPosition->equals($this->exitPosition);
    }

    /**
     * Gets exploration percentage for progress tracking.
     *
     * @return float Percentage of rooms visited (0-100)
     */
    public function getExplorationPercentage(): float
    {
        $totalRooms = count($this->rooms);
        if ($totalRooms === 0) {
            return 0.0;
        }

        return ($this->getVisitedRoomCount() / $totalRooms) * 100;
    }

    /**
     * Checks if a room exists at the specified position.
     *
     * @param Position $position The position to check
     * @return bool True if a room exists at the position, false otherwise
     */
    public function hasRoomAt(Position $position): bool
    {
        return isset($this->rooms[$position->toString()]);
    }
}