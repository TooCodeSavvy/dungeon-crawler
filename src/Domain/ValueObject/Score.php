<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\ValueObject;

/**
 * Represents a player's score as an immutable value object.
 *
 * Score is represented as a non-negative integer and provides
 * methods for adding points and comparing scores.
 */
class Score
{
    /**
     * @param int $value The numeric value of the score (must be non-negative)
     */
    public function __construct(
        private readonly int $value
    ) {
        if ($value < 0) {
            throw new \InvalidArgumentException('Score cannot be negative');
        }
    }

    /**
     * Creates a new Score instance with additional points.
     *
     * @param int $points The number of points to add
     * @return self A new Score instance with the updated value
     * @throws \InvalidArgumentException If points to add is negative
     */
    public function add(int $points): self
    {
        if ($points < 0) {
            throw new \InvalidArgumentException('Cannot add negative points to score');
        }

        return new self($this->value + $points);
    }

    /**
     * Compares this score with another Score instance.
     *
     * @param Score $other The Score to compare with
     * @return int 1 if this score is higher, -1 if lower, 0 if equal
     */
    public function compareTo(Score $other): int
    {
        if ($this->value > $other->value) {
            return 1;
        }

        if ($this->value < $other->value) {
            return -1;
        }

        return 0;
    }

    /**
     * Returns the numeric value of the score.
     *
     * @return int The score value
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Returns a string representation of the score.
     *
     * @return string The score as a string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Creates a new Score instance with zero value.
     *
     * @return self A new Score instance with zero value
     */
    public static function zero(): self
    {
        return new self(0);
    }
}