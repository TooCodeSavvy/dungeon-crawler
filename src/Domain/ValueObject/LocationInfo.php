<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\ValueObject;

/**
 * Value object containing information about a location (room) in the dungeon.
 *
 * Used to return details about a room after movement or discovery.
 */
class LocationInfo
{
    /**
     * @param string $description Descriptive text about the location
     * @param bool $hasTreasure Whether the location contains treasure
     * @param bool $isExit Whether this location is the dungeon exit
     * @param array<Direction> $availableDirections Array of directions the player can move from here
     */
    public function __construct(
        private readonly string $description,
        private readonly bool $hasTreasure,
        private readonly bool $isExit,
        private readonly array $availableDirections
    ) {}

    /**
     * Gets the descriptive text about the location.
     *
     * @return string Description text
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Checks if the location has treasure.
     *
     * @return bool True if treasure is present
     */
    public function hasTreasure(): bool
    {
        return $this->hasTreasure;
    }

    /**
     * Checks if the location is the dungeon exit.
     *
     * @return bool True if this is the exit
     */
    public function isExit(): bool
    {
        return $this->isExit;
    }

    /**
     * Gets an array of available directions to move from this location.
     *
     * @return array<Direction> Available directions
     */
    public function getAvailableDirections(): array
    {
        return $this->availableDirections;
    }

    /**
     * Checks if movement is possible in a specific direction.
     *
     * @param Direction $direction The direction to check
     * @return bool True if movement is possible
     */
    public function canMove(Direction $direction): bool
    {
        foreach ($this->availableDirections as $availableDirection) {
            if ($availableDirection === $direction) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets a comma-separated list of available direction names.
     *
     * @return string Direction names (e.g., "north, east, west")
     */
    public function getAvailableDirectionsText(): string
    {
        if (empty($this->availableDirections)) {
            return "none";
        }

        $directionNames = array_map(
            fn(Direction $dir) => $dir->value,
            $this->availableDirections
        );

        return implode(', ', $directionNames);
    }
}