<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Factory;

use DungeonCrawler\Domain\Entity\Treasure;
use DungeonCrawler\Domain\Entity\TreasureType;

/**
 * Factory class responsible for creating `Treasure` objects in the dungeon crawler game.
 *
 * Treasure generation is based on predefined rarity tiers and game context such as:
 *  - Dungeon level (difficulty)
 *  - Boss encounters
 *  - Random generation for testing
 *
 * Each rarity level defines a pool of treasure templates, which are randomly selected and
 * dynamically described.
 */
final class TreasureFactory
{
    /**
     * Predefined treasure pools categorized by rarity.
     *
     * Each treasure definition includes:
     * - type: TreasureType
     * - name: string
     * - value: int
     */
    private const TREASURE_DEFINITIONS = [
        'common' => [
            ['type' => TreasureType::GOLD, 'name' => 'Copper Coins', 'value' => 5],
            ['type' => TreasureType::GOLD, 'name' => 'Small Gold Pile', 'value' => 10],
            ['type' => TreasureType::GOLD, 'name' => 'Silver Coins', 'value' => 15],
            ['type' => TreasureType::HEALTH_POTION, 'name' => 'Weak Health Potion', 'value' => 15],
            ['type' => TreasureType::HEALTH_POTION, 'name' => 'Minor Health Potion', 'value' => 25],
        ],
        'uncommon' => [
            ['type' => TreasureType::GOLD, 'name' => 'Gold Purse', 'value' => 30],
            ['type' => TreasureType::GOLD, 'name' => 'Large Gold Pile', 'value' => 50],
            ['type' => TreasureType::HEALTH_POTION, 'name' => 'Health Potion', 'value' => 50],
            ['type' => TreasureType::WEAPON, 'name' => 'Iron Dagger', 'value' => 20],
            ['type' => TreasureType::WEAPON, 'name' => 'Rusty Sword', 'value' => 25],
        ],
        'rare' => [
            ['type' => TreasureType::GOLD, 'name' => 'Treasure Chest', 'value' => 75],
            ['type' => TreasureType::HEALTH_POTION, 'name' => 'Greater Health Potion', 'value' => 75],
            ['type' => TreasureType::WEAPON, 'name' => 'Steel Sword', 'value' => 50],
            ['type' => TreasureType::WEAPON, 'name' => 'Battle Axe', 'value' => 60],
        ],
        'epic' => [
            ['type' => TreasureType::GOLD, 'name' => 'Royal Treasury', 'value' => 100],
            ['type' => TreasureType::WEAPON, 'name' => 'Enchanted Blade', 'value' => 75],
            ['type' => TreasureType::WEAPON, 'name' => 'Mithril Sword', 'value' => 85],
            ['type' => TreasureType::ARTIFACT, 'name' => 'Crystal Orb', 'value' => 90],
        ],
        'legendary' => [
            ['type' => TreasureType::ARTIFACT, 'name' => 'Ancient Relic', 'value' => 100],
            ['type' => TreasureType::ARTIFACT, 'name' => 'Dragon Scale', 'value' => 150],
            ['type' => TreasureType::ARTIFACT, 'name' => 'Crown of Kings', 'value' => 200],
            ['type' => TreasureType::WEAPON, 'name' => 'Excalibur', 'value' => 175],
        ],
    ];

    /**
     * Creates treasure based on the dungeon level.
     * The deeper the dungeon, the better the odds for high-rarity treasure.
     *
     * @param int $dungeonLevel The current dungeon level or depth.
     * @return Treasure|null Returns a Treasure or null if none found (chance-based).
     */
    public function createForDifficulty(int $dungeonLevel): ?Treasure
    {
        // Chance of finding treasure increases with dungeon depth
        $treasureChance = min(60 + ($dungeonLevel * 5), 80);

        if (rand(1, 100) > $treasureChance) {
            return null; // No treasure in this room
        }

        $rarity = $this->determineRarity($dungeonLevel);
        return $this->createByRarity($rarity);
    }

    /**
     * Creates a Treasure of a specific rarity.
     *
     * @param string $rarity One of: common, uncommon, rare, epic, legendary.
     * @return Treasure
     * @throws \InvalidArgumentException If rarity is unknown.
     */
    public function createByRarity(string $rarity): Treasure
    {
        if (!isset(self::TREASURE_DEFINITIONS[$rarity])) {
            throw new \InvalidArgumentException("Unknown rarity: {$rarity}");
        }

        $treasures = self::TREASURE_DEFINITIONS[$rarity];
        $definition = $treasures[array_rand($treasures)];

        return new Treasure(
            type: $definition['type'],
            name: $definition['name'],
            value: $definition['value'],
            description: $this->generateDescription($definition['type'], $definition['name'])
        );
    }

    /**
     * Creates a special high-value treasure used as a boss reward.
     *
     * Boss treasures are pulled from epic and legendary pools
     * and receive a value boost.
     *
     * @return Treasure
     */
    public function createBossTreasure(): Treasure
    {
        $bossTreasures = [
            ...self::TREASURE_DEFINITIONS['epic'],
            ...self::TREASURE_DEFINITIONS['legendary'],
        ];

        $definition = $bossTreasures[array_rand($bossTreasures)];

        return new Treasure(
            type: $definition['type'],
            name: $definition['name'] . ' (Boss Reward)',
            value: (int) ($definition['value'] * 1.5),
            description: $this->generateDescription($definition['type'], $definition['name'])
            . ' This treasure radiates power from defeating a mighty foe.'
        );
    }

    /**
     * Determines treasure rarity based on dungeon level and RNG.
     *
     * The higher the level, the better the odds of rolling a high rarity.
     *
     * @param int $dungeonLevel
     * @return string Rarity key.
     */
    private function determineRarity(int $dungeonLevel): string
    {
        $roll = rand(1, 100) + ($dungeonLevel * 2); // Higher levels shift probability

        return match(true) {
            $roll <= 40 => 'common',
            $roll <= 70 => 'uncommon',
            $roll <= 90 => 'rare',
            $roll <= 105 => 'epic',
            default => 'legendary',
        };
    }

    /**
     * Generates a contextual description based on treasure type and name.
     *
     * @param TreasureType $type
     * @param string $name
     * @return string
     */
    private function generateDescription(TreasureType $type, string $name): string
    {
        $descriptions = match($type) {
            TreasureType::GOLD => [
                'Gleaming in the torchlight.',
                'Scattered across the cold stone floor.',
                'Hidden in a dusty corner.',
                'Piled neatly in an ancient container.',
            ],
            TreasureType::HEALTH_POTION => [
                'The liquid inside glows with healing energy.',
                'A faint warmth emanates from the bottle.',
                'Carefully preserved in a padded container.',
                'The cork is sealed with wax bearing a healer\'s mark.',
            ],
            TreasureType::WEAPON => [
                'Despite its age, the edge remains sharp.',
                'Intricate runes are carved along the blade.',
                'The weapon feels perfectly balanced in your hands.',
                'It hums with barely contained power.',
            ],
            TreasureType::ARTIFACT => [
                'Ancient power thrums within this mysterious object.',
                'The artifact seems to bend light around itself.',
                'Touching it sends shivers down your spine.',
                'Lost for centuries, spoken of only in legends.',
            ],
        };

        return sprintf('%s %s', $name, $descriptions[array_rand($descriptions)]);
    }

    /**
     * Creates a completely random treasure from all available definitions.
     * Useful for testing, sandboxing, or debug mode.
     *
     * @return Treasure
     */
    public function createRandom(): Treasure
    {
        $allTreasures = array_merge(
            ...array_values(self::TREASURE_DEFINITIONS)
        );

        $definition = $allTreasures[array_rand($allTreasures)];

        return new Treasure(
            type: $definition['type'],
            name: $definition['name'],
            value: $definition['value'],
            description: $this->generateDescription($definition['type'], $definition['name'])
        );
    }
}
