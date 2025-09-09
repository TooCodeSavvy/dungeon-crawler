<?php

declare(strict_types=1);

namespace DungeonCrawler\Domain\Entity;

use DungeonCrawler\Domain\ValueObject\Health;
use DungeonCrawler\Domain\ValueObject\Position;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Represents the player character in the dungeon crawler game.
 *
 * The player has a unique ID, name, position, health, attack power,
 * and experience points. The player can move, attack, heal, take damage,
 * and gain experience from combat.
 */
class Player
{
    /**
     * Unique identifier for the player.
     *
     * @var UuidInterface
     */
    private UuidInterface $id;

    /**
     * Name of the player.
     *
     * @var string
     */
    private string $name;

    /**
     * The player's health value object.
     *
     * @var Health
     */
    private Health $health;

    /**
     * The player's current position in the dungeon.
     *
     * @var Position
     */
    private Position $position;

    /**
     * Base attack power of the player.
     *
     * @var int
     */
    private int $attackPower;

    /**
     * Accumulated experience points from defeating monsters.
     *
     * @var int
     */
    private int $experiencePoints = 0;

    /**
     * Player constructor.
     *
     * @param string  $name Player name; must not be empty.
     * @param Health $health Player's initial health.
     * @param Position $position Starting position in the dungeon.
     * @param int $attackPower Player's base attack power; must be positive.
     * @param UuidInterface|null $id Optional UUID (auto-generated if not provided).
     *
     * @throws \InvalidArgumentException When name is empty or attack power is invalid.
     */
    public function __construct(
        string $name,
        Health $health,
        Position $position,
        int $attackPower = 20,
        ?UuidInterface $id = null
    ) {
        if (empty($name)) {
            throw new \InvalidArgumentException('Player name cannot be empty');
        }

        if ($attackPower <= 0) {
            throw new \InvalidArgumentException('Attack power must be positive');
        }

        $this->id = $id ?? Uuid::uuid4();
        $this->name = $name;
        $this->health = $health;
        $this->position = $position;
        $this->attackPower = $attackPower;
    }

    /**
     * Reduce the player's health by a given damage amount.
     *
     * @param int $damage Damage to apply.
     */
    public function takeDamage(int $damage): void
    {
        $this->health = $this->health->reduce($damage);
    }

    /**
     * Increase the player's health by a given healing amount.
     *
     * @param int $amount Healing amount.
     */
    public function heal(int $amount): void
    {
        $this->health = $this->health->heal($amount);
    }

    /**
     * Move the player to a new position.
     *
     * @param Position $newPosition New location in the dungeon.
     */
    public function moveTo(Position $newPosition): void
    {
        $this->position = $newPosition;
    }

    /**
     * Perform an attack and return the damage value.
     *
     * Damage is randomized with a 20% variance for combat unpredictability.
     *
     * @return int Damage dealt by the player.
     */
    public function attack(): int
    {
        $variance = (int) ($this->attackPower * 0.2);
        return rand($this->attackPower - $variance, $this->attackPower + $variance);
    }

    /**
     * Increase the player's experience points.
     *
     * @param int $points Points to add; must be non-negative.
     *
     * @throws \InvalidArgumentException If points are negative.
     */
    public function gainExperience(int $points): void
    {
        if ($points < 0) {
            throw new \InvalidArgumentException('Experience points cannot be negative');
        }

        $this->experiencePoints += $points;
    }

    /**
     * Get the player's UUID.
     *
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * Get the player's name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the player's current health object.
     *
     * @return Health
     */
    public function getHealth(): Health
    {
        return $this->health;
    }

    /**
     * Get the player's current position.
     *
     * @return Position
     */
    public function getPosition(): Position
    {
        return $this->position;
    }

    /**
     * Get the player's base attack power.
     *
     * @return int
     */
    public function getAttackPower(): int
    {
        return $this->attackPower;
    }

    /**
     * Get the player's accumulated experience points.
     *
     * @return int
     */
    public function getExperiencePoints(): int
    {
        return $this->experiencePoints;
    }

    /**
     * Check if the player is still alive.
     *
     * @return bool True if health > 0, false otherwise.
     */
    public function isAlive(): bool
    {
        return !$this->health->isDead();
    }
}
