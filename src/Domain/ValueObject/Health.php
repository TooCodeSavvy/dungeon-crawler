<?php

declare(strict_types=1);

namespace DungeonCrawler\Domain\ValueObject;

/**
 * Health value object representing an entity's health points
 *
 * This is an immutable value object following DDD principles.
 * All methods that modify health return new instances rather than mutating state.
 *
 * @package    DungeonCrawler\ValueObject\ValueObject
 * @author     TooCodeSavvy
 */
final class Health
{
    /**
     * @var int Current health points
     */
    private int $current;

    /**
     * @var int Maximum health points
     */
    private int $max;

    /**
     * Create a new Health instance
     *
     * @param int $current Current health value
     * @param int $max Maximum health value
     *
     * @throws \InvalidArgumentException When health values are invalid
     */
    public function __construct(int $current, int $max)
    {
        // Validate max health first as it's used in subsequent validations
        if ($max <= 0) {
            throw new \InvalidArgumentException('Max health must be positive');
        }

        if ($current < 0) {
            throw new \InvalidArgumentException('Current health cannot be negative');
        }

        if ($current > $max) {
            throw new \InvalidArgumentException('Current health cannot exceed max health');
        }

        $this->current = $current;
        $this->max = $max;
    }

    /**
     * Factory method to create a Health instance with full health
     *
     * @param int $max Maximum health value
     * @return self New Health instance at maximum value
     */
    public static function full(int $max): self
    {
        return new self($max, $max);
    }

    /**
     * Create a new Health instance with reduced health
     *
     * Damage is clamped to prevent negative health values.
     * This method ensures immutability by returning a new instance.
     *
     * @param int $damage Amount of damage to apply
     * @return self New Health instance with reduced value
     *
     * @throws \InvalidArgumentException When damage is negative
     */
    public function reduce(int $damage): self
    {
        if ($damage < 0) {
            throw new \InvalidArgumentException('Damage cannot be negative');
        }

        // Use max() to clamp health at 0, preventing negative values
        return new self(max(0, $this->current - $damage), $this->max);
    }

    /**
     * Create a new Health instance with increased health
     *
     * Healing is capped at maximum health value.
     * This method ensures immutability by returning a new instance.
     *
     * @param int $amount Amount of health to restore
     * @return self New Health instance with increased value
     *
     * @throws \InvalidArgumentException When heal amount is negative
     */
    public function heal(int $amount): self
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Heal amount cannot be negative');
        }

        // Use min() to cap health at max value
        return new self(min($this->max, $this->current + $amount), $this->max);
    }

    /**
     * Get current health value
     *
     * @return int Current health points
     */
    public function getValue(): int
    {
        return $this->current;
    }

    /**
     * Get maximum health value
     *
     * @return int Maximum health points
     */
    public function getMax(): int
    {
        return $this->max;
    }

    /**
     * Calculate health as a percentage of maximum
     *
     * Useful for displaying health bars or determining entity condition.
     *
     * @return float Health percentage (0.0 to 100.0)
     */
    public function getPercentage(): float
    {
        return ($this->current / $this->max) * 100;
    }

    /**
     * Check if health has reached zero
     *
     * @return bool True if current health is 0 or less
     */
    public function isDead(): bool
    {
        return $this->current <= 0;
    }

    /**
     * Check if health is at maximum
     *
     * Useful for determining if healing is needed.
     *
     * @return bool True if current health equals max health
     */
    public function isFull(): bool
    {
        return $this->current === $this->max;
    }

    /**
     * Create string representation of health
     *
     * Useful for debugging and logging.
     *
     * @return string Health in format "current/max"
     */
    public function __toString(): string
    {
        return sprintf('%d/%d', $this->current, $this->max);
    }

    /**
     * Check equality with another Health instance
     *
     * Value objects should be compared by value, not reference.
     *
     * @param Health $other Health instance to compare
     * @return bool True if both current and max values are equal
     */
    public function equals(Health $other): bool
    {
        return $this->current === $other->current && $this->max === $other->max;
    }
}