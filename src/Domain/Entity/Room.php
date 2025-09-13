<?php

declare(strict_types=1);

namespace DungeonCrawler\Domain\Entity;

use DungeonCrawler\Domain\ValueObject\Direction;
use DungeonCrawler\Domain\ValueObject\Position;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Represents a Room in the dungeon crawler game.
 *
 * Each Room has a unique ID, a position on the dungeon map,
 * a textual description, optional monster and treasure,
 * exit status, visitation state, and directional connections to adjacent rooms.
 */
final class Room
{
    /**
     * Unique identifier for the room.
     *
     * @var UuidInterface
     */
    private UuidInterface $id;

    /**
     * The position of the room in the dungeon grid.
     *
     * @var Position
     */
    private Position $position;

    /**
     * A descriptive text about the room's environment.
     *
     * @var string
     */
    private string $description;

    /**
     * The monster inhabiting the room, if any.
     *
     * @var Monster|null
     */
    private ?Monster $monster;

    /**
     * The treasure located in the room, if any.
     *
     * @var Treasure|null
     */
    private ?Treasure $treasure;

    /**
     * Indicates if this room is the dungeon exit.
     *
     * @var bool
     */
    private bool $isExit;

    /**
     * Indicates if the room has been visited by the player.
     *
     * @var bool
     */
    private bool $visited = false;

    /**
     * Directional connections indicating accessible adjacent rooms.
     * Maps Direction values to booleans (true if connected).
     *
     * @var array<string, bool>
     */
    private array $connections = [];

    /**
     * Room constructor.
     *
     * @param Position $position The room's position on the grid.
     * @param string $description Optional description; generated if empty.
     * @param Monster|null $monster Optional monster present in the room.
     * @param Treasure|null $treasure Optional treasure present in the room.
     * @param bool $isExit Whether this room is the dungeon exit.
     * @param UuidInterface|null $id Optional UUID; generated if null.
     */
    public function __construct(Position $position, string $description = '', ?Monster $monster = null, ?Treasure $treasure = null, bool $isExit = false, ?UuidInterface $id = null)
    {
        $this->id = $id ?? Uuid::uuid4();
        $this->position = $position;
        $this->description = $description ?: $this->generateDescription();
        $this->monster = $monster;
        $this->treasure = $treasure;
        $this->isExit = $isExit;

        // Initialize all possible directions as not connected (blocked)
        foreach (Direction::cases() as $direction) {
            $this->connections[$direction->value] = false;
        }
    }

    /**
     * Creates a connection in the given direction from this room.
     *
     * @param Direction $direction The direction to connect.
     */
    public function connectTo(Direction $direction): void
    {
        $this->connections[$direction->value] = true;
    }

    /**
     * Checks if this room has a connection in the specified direction.
     *
     * @param Direction $direction The direction to check.
     * @return bool True if connected, false otherwise.
     */
    public function hasConnection(Direction $direction): bool
    {
        return $this->connections[$direction->value] ?? false;
    }

    /**
     * Returns an array of Directions representing accessible connections.
     *
     * @return Direction[] List of directions where this room is connected.
     */
    public function getAvailableDirections(): array
    {
        return array_filter(
            Direction::cases(),
            fn(Direction $dir) => $this->hasConnection($dir)
        );
    }

    /**
     * Marks the room as visited by the player.
     */
    public function enter(): void
    {
        $this->visited = true;
    }

    /**
     * Removes the monster from the room.
     */
    public function removeMonster(): void
    {
        $this->monster = null;
    }

    /**
     * Removes and returns the treasure from the room.
     *
     * @return Treasure|null The removed treasure or null if none was present.
     */
    public function removeTreasure(): ?Treasure
    {
        $treasure = $this->treasure;
        $this->treasure = null;
        return $treasure;
    }

    /**
     * Generates a random room description from predefined options.
     *
     * @return string Generated room description.
     */
    private function generateDescription(): string
    {
        $descriptions = [
            'A dimly lit chamber with stone walls covered in moss.',
            'A spacious hall with ancient pillars reaching to the ceiling.',
            'A narrow corridor with flickering torches on the walls.',
            'A circular room with mysterious symbols etched into the floor.',
            'A cold chamber with the sound of dripping water echoing.',
        ];
        return $descriptions[array_rand($descriptions)];
    }

    // Getters

    /**
     * Returns the unique identifier for the room.
     *
     * @return UuidInterface The room's UUID.
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * Returns the position of the room on the dungeon grid.
     *
     * @return Position The room's position.
     */
    public function getPosition(): Position
    {
        return $this->position;
    }

    /**
     * Returns the descriptive text for the room.
     *
     * @return string The room description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Returns the monster in the room, if any.
     *
     * @return Monster|null The monster or null if none.
     */
    public function getMonster(): ?Monster
    {
        return $this->monster;
    }

    /**
     * Returns the treasure in the room, if any.
     *
     * @return Treasure|null The treasure or null if none.
     */
    public function getTreasure(): ?Treasure
    {
        return $this->treasure;
    }

    /**
     * Checks if this room is the dungeon exit.
     *
     * @return bool True if this room is the exit.
     */
    public function isExit(): bool
    {
        return $this->isExit;
    }

    /**
     * Checks if the room has been visited.
     *
     * @return bool True if visited.
     */
    public function isVisited(): bool
    {
        return $this->visited;
    }

    /**
     * Checks if the room currently has a living monster.
     *
     * @return bool True if there is a living monster.
     */
    public function hasMonster(): bool
    {
        return $this->monster !== null && $this->monster->isAlive();
    }

    /**
     * Checks if the room currently contains treasure.
     *
     * @return bool True if treasure is present.
     */
    public function hasTreasure(): bool
    {
        return $this->treasure !== null;
    }

    /**
     * Checks if the room is empty (no monster, no treasure, and not exit).
     *
     * @return bool True if the room is empty.
     */
    public function isEmpty(): bool
    {
        return !$this->hasMonster() && !$this->hasTreasure() && !$this->isExit;
    }

    /**
     * Returns a human-readable name for the room.
     *
     * @return string The room's name/identifier.
     */
    public function getName(): string
    {
        if ($this->isExit) {
            return "Exit Room";
        }

        if (!empty($this->description)) {
            // Extract first part of description as a name
            $parts = explode(' ', $this->description);
            $prefix = array_slice($parts, 0, min(3, count($parts)));
            return implode(' ', $prefix) . "...";
        }

        // Fallback to position-based name
        return "Room at " . $this->position;
    }
}
