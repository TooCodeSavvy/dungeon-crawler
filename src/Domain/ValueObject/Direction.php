<?php

declare(strict_types=1);

namespace DungeonCrawler\Domain\ValueObject;

/**
 * Enum Direction
 *
 * Represents the four cardinal directions used for movement within the dungeon.
 * Includes helper methods to parse user input and retrieve the opposite direction.
 *
 * Example usage:
 *   $direction = Direction::fromString('north');  // Direction::NORTH
 *   $opposite = $direction->opposite();           // Direction::SOUTH
 */
enum Direction: string
{
    case NORTH = 'north';
    case SOUTH = 'south';
    case EAST = 'east';
    case WEST = 'west';

    /**
     * Converts a string to a Direction enum value.
     *
     * @param string $direction The direction string (case-insensitive)
     * @return self The corresponding Direction enum value
     * @throws \InvalidArgumentException If the string doesn't match a valid direction
     */
    public static function fromString(string $direction): self
    {
        $direction = strtolower(trim($direction));

        echo "DEBUG: Direction::fromString received: '{$direction}'\n";

        return match ($direction) {
            'north', 'n' => self::NORTH,
            'east', 'e' => self::EAST,
            'south', 's' => self::SOUTH,
            'west', 'w' => self::WEST,
            default => throw new \InvalidArgumentException("Invalid direction: {$direction}")
        };
    }

    /**
     * Returns the opposite direction (e.g., NORTH → SOUTH).
     *
     * Useful for determining two-way room connections or reversing movement.
     *
     * @return self The opposite Direction enum case.
     */
    public function opposite(): self
    {
        return match ($this) {
            self::NORTH => self::SOUTH,
            self::SOUTH => self::NORTH,
            self::EAST  => self::WEST,
            self::WEST  => self::EAST,
        };
    }
}
