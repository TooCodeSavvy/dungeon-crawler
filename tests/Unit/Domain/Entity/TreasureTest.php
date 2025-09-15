<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use PHPUnit\Framework\TestCase;
use DungeonCrawler\Domain\Entity\Treasure;
use DungeonCrawler\Domain\Entity\TreasureType;
use DungeonCrawler\Domain\Entity\Player;
use DungeonCrawler\Domain\ValueObject\Health;
use DungeonCrawler\Domain\ValueObject\Position;

/**
 * @covers \DungeonCrawler\Domain\Entity\Treasure
 *
 * Unit tests for the Treasure entity.
 *
 * This test suite verifies:
 * - Validation of treasure construction (e.g., invalid name/value).
 * - Application of treasure effects on the Player (XP, healing).
 * - Rarity classification logic.
 * - Factory methods producing valid treasures.
 */
final class TreasureTest extends TestCase
{
    /**
     * Ensures an exception is thrown when creating treasure with an empty name.
     */
    public function test_it_throws_exception_for_empty_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Treasure name cannot be empty');

        new Treasure(
            TreasureType::GOLD,
            '',
            10
        );
    }

    /**
     * Ensures an exception is thrown when creating treasure with zero value.
     */
    public function test_it_throws_exception_for_zero_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Treasure value must be positive');

        new Treasure(
            TreasureType::GOLD,
            'Gold Pile',
            0
        );
    }

    /**
     * Verifies that collecting gold grants the correct amount of experience to the player.
     */
    public function test_apply_gold_adds_experience(): void
    {
        $player = $this->createPlayerWithSpy();
        $treasure = new Treasure(
            TreasureType::GOLD,
            'Coins',
            20
        );

        $message = $treasure->applyTo($player);

        $this->assertSame('You found 20 gold coins! (+20 XP)', $message);
        $this->assertSame(20, $player->getExperience());
    }

    /**
     * Verifies that a health potion restores the correct amount of health to the player.
     */
    public function test_apply_health_potion_restores_health(): void
    {
        $player = $this->createPlayerWithHealth(50, 100);
        $treasure = new Treasure(
            TreasureType::HEALTH_POTION,
            'Health Potion',
            30
        );

        $message = $treasure->applyTo($player);

        $this->assertStringContainsString('restore 30 health points', $message);
        $this->assertSame(80, $player->getHealth()->getValue());
    }

    /**
     * Ensures that rarity classification logic produces expected labels.
     */
    public function test_rarity_classification(): void
    {
        $common = new Treasure(TreasureType::GOLD, 'Test', 10);
        $rare = new Treasure(TreasureType::WEAPON, 'Test', 75);
        $legendary = new Treasure(TreasureType::ARTIFACT, 'Test', 200);

        $this->assertSame('Common', $common->getDisplayInfo()['rarity']);
        $this->assertSame('Rare', $rare->getDisplayInfo()['rarity']);
        $this->assertSame('Legendary', $legendary->getDisplayInfo()['rarity']);
    }

    /**
     * Ensures factory method creates a correctly configured treasure instance.
     */
    public function test_factory_creates_correct_type(): void
    {
        $treasure = Treasure::createMinorHealthPotion();

        $this->assertSame(TreasureType::HEALTH_POTION, $treasure->getType());
        $this->assertSame('Minor Health Potion', $treasure->getName());
        $this->assertGreaterThan(0, $treasure->getValue());
    }

    /**
     * Creates a testable Player instance that tracks experience.
     *
     * @return Player A player with custom XP tracking.
     */
    private function createPlayerWithSpy(): Player
    {
        return new class () extends Player {
            private int $xp = 0;

            public function __construct()
            {
                parent::__construct('TestHero', new Health(100, 100), new Position(0, 0));
            }

            public function gainExperience(int $xp): void
            {
                $this->xp += $xp;
            }

            public function getExperience(): int
            {
                return $this->xp;
            }
        };
    }

    /**
     * Creates a testable Player with specific health values.
     *
     * @param int $current The current health.
     * @param int $max     The maximum health.
     *
     * @return Player A player with controlled health state.
     */
    private function createPlayerWithHealth(int $current, int $max): Player
    {
        return new class ($current, $max) extends Player {
            public function __construct(int $current, int $max)
            {
                parent::__construct('TestHero', new Health($current, $max), new Position(0, 0));
            }

            public function gainExperience(int $xp): void
            {
                // No-op in this test variant
            }
        };
    }
}
