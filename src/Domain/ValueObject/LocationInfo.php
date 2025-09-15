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
     * Checks if the location is the dungeon exit.
     *
     * @return bool True if this is the exit
     */
    public function isExit(): bool
    {
        return $this->isExit;
    }

}