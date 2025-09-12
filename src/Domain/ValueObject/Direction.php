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
     * Converts a string input into a Direction enum value.
     *
     * Accepts full direction names or single-character aliases (e.g., 'n', 's').
     *
     * @param string $direction The user-provided direction string.
     * @return self The corresponding Direction enum case.
     *
     * @throws \InvalidArgumentException If the input does not match a valid direction.
     */
    public static function fromString(string $direction): self
    {
        $normalized = strtolower(trim($direction));

        return match ($normalized) {
            'n', 'north' => self::NORTH,
            's', 'south' => self::SOUTH,
            'e', 'east'  => self::EAST,
            'w', 'west'  => self::WEST,
            default => throw new \InvalidArgumentException("Invalid direction: {$direction}"),
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
