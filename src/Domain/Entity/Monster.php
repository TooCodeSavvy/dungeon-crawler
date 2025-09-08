<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Entity;

use DungeonCrawler\Domain\ValueObject\Health;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Represents a Monster entity in the dungeon crawler game.
 *
 * Monsters have unique IDs, names, health, attack power, and experience rewards.
 * Supports taking damage, attacking with slight damage variance,
 * and factory methods to create common monster types.
 */
class Monster
{
    /**
     * Unique identifier for the monster.
     *
     * @var UuidInterface
     */
    private UuidInterface $id;


    /**
     * The name of the monster (e.g., "Goblin", "Orc").
     *
     * @var string
     */
    private string $name;

    /**
     * The current health of the monster.
     *
     * @var Health
     */
    private Health $health;

    /**
     * The monster's base attack power.
     *
     * @var int
     */
    private int $attackPower;

    /**
     * Amount of experience rewarded when the monster is defeated.
     *
     * @var int
     */
    private int $experienceReward;

    /**
     * Monster constructor.
     *
     * @param string $name The monster's name; must not be empty.
     * @param Health $health The health value object representing the monster's health.
     * @param int $attackPower The base attack power; must be positive.
     * @param int $experienceReward Experience points rewarded to the player upon monster's defeat; cannot be negative.
     * @param UuidInterface|null $id Optional UUID; if null, a new UUID is generated.
     *
     * @throws \InvalidArgumentException When validation fails for inputs.
     */
    public function __construct(
        string $name,
        Health $health,
        int $attackPower,
        int $experienceReward = 10,
        ?UuidInterface $id = null
    ) {
        if (empty($name)) {
            throw new \InvalidArgumentException('Monster name cannot be empty');
        }
        if ($attackPower <= 0) {
            throw new \InvalidArgumentException('Attack power must be positive');
        }
        if ($experienceReward < 0) {
            throw new \InvalidArgumentException('Experience reward cannot be negative');
        }

        $this->name = $name;
        $this->health = $health;
        $this->attackPower = $attackPower;
        $this->experienceReward = $experienceReward;
        $this->id = $id ?? Uuid::uuid4();
    }

    /**
     * Applies damage to the monster, reducing its health.
     *
     * @param int $damage Amount of damage inflicted.
     * @return void
     */
    public function takeDamage(int $damage): void
    {
        $this->health = $this->health->reduce($damage);
    }

    /**
     * Calculates and returns the monster's attack damage for a turn.
     *
     * Uses a small variance of ±10% of attack power for attack randomness.
     *
     * @return int The damage inflicted by this attack.
     */
    public function attack(): int
    {
        $variance = (int) ($this->attackPower * 0.1);
        return rand($this->attackPower - $variance, $this->attackPower + $variance);
    }

    /**
     * Checks whether the monster is still alive (health > 0).
     *
     * @return bool True if alive; false if dead.
     */
    public function isAlive(): bool
    {
        return !$this->health->isDead();
    }

    /**
     * Gets the unique identifier of the monster.
     *
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * Gets the name of the monster.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the current health value object of the monster.
     *
     * @return Health
     */
    public function getHealth(): Health
    {
        return $this->health;
    }

    /**
     * Gets the base attack power of the monster.
     *
     * @return int
     */
    public function getAttackPower(): int
    {
        return $this->attackPower;
    }

    /**
     * Gets the experience reward points given when the monster is defeated.
     *
     * @return int
     */
    public function getExperienceReward(): int
    {
        return $this->experienceReward;
    }
}
