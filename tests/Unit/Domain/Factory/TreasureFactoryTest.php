<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Factory;

use PHPUnit\Framework\TestCase;
use DungeonCrawler\Domain\Factory\TreasureFactory;
use DungeonCrawler\Domain\Entity\Treasure;
use DungeonCrawler\Domain\Entity\TreasureType;

/**
 * Unit tests for the TreasureFactory class.
 *
 * This test suite ensures that treasure creation behaves as expected across:
 * - Different rarity tiers
 * - Dungeon level influence
 * - Boss loot generation
 * - Full-random treasure generation
 *
 * It also verifies that invalid inputs throw appropriate exceptions.
 */
final class TreasureFactoryTest extends TestCase
{
    /**
     * @var TreasureFactory
     */
    private TreasureFactory $factory;

    /**
     * Initializes a new TreasureFactory instance before each test.
     */
    protected function setUp(): void
    {
        $this->factory = new TreasureFactory();
    }

    /**
     * @covers \DungeonCrawler\Domain\Factory\TreasureFactory::createByRarity
     *
     * Ensures that treasures created from all valid rarity levels:
     * - Are instances of the Treasure class
     * - Have a non-empty name
     * - Have a positive value
     */
    public function test_create_by_valid_rarity_returns_treasure(): void
    {
        $rarities = ['common', 'uncommon', 'rare', 'epic', 'legendary'];

        foreach ($rarities as $rarity) {
            $treasure = $this->factory->createByRarity($rarity);

            $this->assertInstanceOf(Treasure::class, $treasure);
            $this->assertNotEmpty($treasure->getName(), "Name should not be empty for {$rarity} treasure");
            $this->assertGreaterThan(0, $treasure->getValue(), "Value should be > 0 for {$rarity} treasure");
        }
    }

    /**
     * @covers \DungeonCrawler\Domain\Factory\TreasureFactory::createByRarity
     *
     * Verifies that passing an invalid rarity string to createByRarity()
     * throws an InvalidArgumentException.
     */
    public function test_create_by_invalid_rarity_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown rarity: mythical');

        $this->factory->createByRarity('mythical');
    }

    /**
     * @covers \DungeonCrawler\Domain\Factory\TreasureFactory::createBossTreasure
     *
     * Ensures that a boss treasure:
     * - Is a valid Treasure instance
     * - Contains the suffix "Boss Reward" in its name
     * - Has a high value (boosted from epic/legendary tiers)
     */
    public function test_create_boss_treasure_returns_powerful_item(): void
    {
        $treasure = $this->factory->createBossTreasure();

        $this->assertInstanceOf(Treasure::class, $treasure);
        $this->assertStringContainsString('Boss Reward', $treasure->getName());
        $this->assertGreaterThanOrEqual(100, $treasure->getValue());
    }

    /**
     * @covers \DungeonCrawler\Domain\Factory\TreasureFactory::createForDifficulty
     *
     * Because createForDifficulty() is RNG-based, this test runs it multiple times
     * to assert that:
     * - At least one treasure is generated
     * - At least one null is returned (no treasure found)
     */
    public function test_create_for_difficulty_returns_treasure_or_null(): void
    {
        $found = false;
        $missed = false;

        for ($i = 0; $i < 50; $i++) {
            $result = $this->factory->createForDifficulty(1);
            if ($result instanceof Treasure) {
                $found = true;
            } else {
                $missed = true;
            }

            if ($found && $missed) {
                break;
            }
        }

        $this->assertTrue($found, 'Expected at least one treasure to be generated.');
        $this->assertTrue($missed, 'Expected at least one null result (no treasure).');
    }

    /**
     * @covers \DungeonCrawler\Domain\Factory\TreasureFactory::createRandom
     *
     * Verifies that createRandom() generates a valid Treasure instance
     * with a non-empty name, positive value, and a valid TreasureType.
     */
    public function test_create_random_returns_valid_treasure(): void
    {
        $treasure = $this->factory->createRandom();

        $this->assertInstanceOf(Treasure::class, $treasure);
        $this->assertNotEmpty($treasure->getName(), 'Random treasure should have a name.');
        $this->assertGreaterThan(0, $treasure->getValue(), 'Random treasure value should be > 0.');
        $this->assertInstanceOf(TreasureType::class, $treasure->getType(), 'Random treasure should have a valid type.');
    }
}
