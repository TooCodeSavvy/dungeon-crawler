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
     * @var array<int, Item> The player's inventory items
     */
    private array $inventory = [];

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
     * Factory method to create a new player with default health, position, and attack power.
     *
     * @param string $name The name of the player.
     * @return self A new Player instance.
     */
    public static function create(string $name): self
    {
        // Use the full method to create a health instance with full health (current = max)
        $defaultHealth = Health::full(100);  // current = max = 100
        $defaultPosition = new Position(0, 0);  // Initial position (0, 0)
        $defaultAttackPower = 20;  // Default attack power

        return new self($name, $defaultHealth, $defaultPosition, $defaultAttackPower);
    }


    /**
     * Gets the player's inventory.
     *
     * @return array<int, Item> Array of inventory items
     */
    public function getInventory(): array
    {
        return $this->inventory;
    }


    /**
     * Adds an item to the player's inventory.
     *
     * @param Item $item The item to add
     * @return void
     */
    public function addItem(Item $item): void
    {
        $this->inventory[] = $item;
    }

    /**
     * Removes an item from the player's inventory by its ID.
     *
     * @param UuidInterface $itemId The ID of the item to remove
     * @return Item|null The removed item or null if not found
     */
    public function removeItem(UuidInterface $itemId): ?Item
    {
        foreach ($this->inventory as $key => $item) {
            if ($item->getId()->equals($itemId)) {
                $removedItem = $this->inventory[$key];
                unset($this->inventory[$key]);
                return $removedItem;
            }
        }

        return null;
    }

    /**
     * Checks if the player has a specific item by its ID.
     *
     * @param UuidInterface $itemId The ID of the item to check
     * @return bool True if the player has the item
     */
    public function hasItem(UuidInterface $itemId): bool
    {
        foreach ($this->inventory as $item) {
            if ($item->getId()->equals($itemId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the total count of items in the player's inventory.
     *
     * @return int The number of items
     */
    public function getInventoryCount(): int
    {
        return count($this->inventory);
    }

    /**
     * Sets the player's position to a new location.
     *
     * @param Position $position The new position
     */
    public function setPosition(Position $position): void
    {
        $this->position = $position;
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
