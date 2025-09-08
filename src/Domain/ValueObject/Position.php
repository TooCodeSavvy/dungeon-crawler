<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\ValueObject;

use DungeonCrawler\Domain\ValueObject\Direction;

/**
 * Represents a position on a 2D grid within the dungeon crawler game.
 *
 * Coordinates must be non-negative integers.
 * Supports moving in cardinal directions by returning new Position instances,
 * equality checks, and string representation.
 */
final class Position
{
    /**
     * X coordinate (horizontal position), zero or positive integer.
     *
     * @var int
     */
    private readonly int $x;

    /**
     * Y coordinate (vertical position), zero or positive integer.
     *
     * @var int
     */
    private readonly int $y;

    /**
     * Constructor.
     *
     * @param int $x The x-coordinate; must be non-negative.
     * @param int $y The y-coordinate; must be non-negative.
     *
     * @throws \InvalidArgumentException if $x or $y is negative.
     */
    public function __construct(int $x, int $y)
    {
        if ($x < 0 || $y < 0) {
            throw new \InvalidArgumentException('Position coordinates must be non-negative');
        }

        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Returns a new Position moved by one step in the given direction.
     *
     * @param Direction $direction The direction to move.
     *
     * @return self New Position after moving one unit in the specified direction.
     */
    public function move(Direction $direction): self
    {
        return match ($direction) {
            Direction::NORTH => new self($this->x, $this->y - 1),
            Direction::SOUTH => new self($this->x, $this->y + 1),
            Direction::EAST  => new self($this->x + 1, $this->y),
            Direction::WEST  => new self($this->x - 1, $this->y),
        };
    }

    /**
     * Checks if this Position equals another Position.
     *
     * @param Position $other The other Position to compare.
     *
     * @return bool True if both coordinates are equal, false otherwise.
     */
    public function equals(Position $other): bool
    {
        return $this->x === $other->x && $this->y === $other->y;
    }

    /**
     * Returns the X coordinate.
     *
     * @return int The x-coordinate.
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * Returns the Y coordinate.
     *
     * @return int The y-coordinate.
     */
    public function getY(): int
    {
        return $this->y;
    }

    /**
     * Returns a string representation of the Position.
     *
     * Format: "[x,y]"
     *
     * @return string The string representation of the position.
     */
    public function toString(): string
    {
        return sprintf('[%d,%d]', $this->x, $this->y);
    }
}
