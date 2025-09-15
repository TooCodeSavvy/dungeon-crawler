<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Entity;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Represents an item that can be collected and used by the player.
 */
class Item
{
    /**
     * @param UuidInterface $id Unique identifier for the item
     * @param string $name Name of the item
     * @param string $description Description of the item
     * @param string $type Type of item (e.g., 'weapon', 'potion', 'gold')
     * @param int $value Numeric value of the item (damage for weapons, health for potions, amount for gold)
     */
    public function __construct(
        private readonly UuidInterface $id,
        private readonly string $name,
        private readonly string $description,
        private readonly string $type,
        private readonly int $value
    ) {}

    /**
     * Gets the unique ID of the item.
     *
     * @return UuidInterface The item's ID
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * Gets the name of the item.
     *
     * @return string The item's name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the description of the item.
     *
     * @return string The item's description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Gets the type of the item.
     *
     * @return string The item's type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets the value of the item.
     *
     * @return int The item's value
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Creates a new gold item with the specified amount.
     *
     * @param int $amount The amount of gold
     * @return self The created gold item
     */
    public static function createGold(int $amount): self
    {
        return new self(
            Uuid::uuid4(),
            'Gold',
            'Shiny gold coins',
            'gold',
            $amount
        );
    }

    /**
     * Creates a new health potion item with the specified healing value.
     *
     * @param int $healingValue The amount of health restored
     * @return self The created health potion
     */
    public static function createHealthPotion(int $healingValue): self
    {
        return new self(
            Uuid::uuid4(),
            'Health Potion',
            'A red potion that restores ' . $healingValue . ' health points',
            'potion',
            $healingValue
        );
    }

    /**
     * Creates a new weapon item with the specified damage value.
     *
     * @param string $name The name of the weapon
     * @param int $damageValue The damage value of the weapon
     * @return self The created weapon
     */
    public static function createWeapon(string $name, int $damageValue): self
    {
        return new self(
            Uuid::uuid4(),
            $name,
            'A weapon that deals ' . $damageValue . ' damage',
            'weapon',
            $damageValue
        );
    }
}