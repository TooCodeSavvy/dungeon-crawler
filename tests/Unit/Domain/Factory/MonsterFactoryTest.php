<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Factory;

use DungeonCrawler\Domain\Entity\Monster;
use DungeonCrawler\Domain\Factory\MonsterFactory;
use DungeonCrawler\Domain\Factory\MonsterFactoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DungeonCrawler\Domain\Factory\MonsterFactory
 *
 * Unit tests for MonsterFactory, ensuring that each predefined monster type
 * (Goblin, Orc, Dragon) is correctly instantiated with expected attributes.
 *
 * This test uses MonsterFactoryInterface to enforce interface-based design
 * and to support future mocking or replacement of the factory if needed.
 */
final class MonsterFactoryTest extends TestCase
{
    /** @var MonsterFactoryInterface */
    private MonsterFactoryInterface $factory;

    /**
     * Set up a fresh instance of the factory before each test.
     * This ensures isolation and consistency across all test cases.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new MonsterFactory();
    }

    /**
     * Test that the Goblin factory method returns a properly initialized Monster.
     */
    public function testCreateGoblin(): void
    {
        $monster = $this->factory->createGoblin();

        // Assert that the monster is correctly configured
        $this->assertInstanceOf(Monster::class, $monster);
        $this->assertSame('Goblin', $monster->getName());
        $this->assertSame(30, $monster->getHealth()->getValue());
        $this->assertSame(10, $monster->getAttackPower());
        $this->assertSame(15, $monster->getExperienceReward());
    }

    /**
     * Test that the Orc factory method returns a properly initialized Monster.
     */
    public function testCreateOrc(): void
    {
        $monster = $this->factory->createOrc();

        // Assert that the monster is correctly configured
        $this->assertInstanceOf(Monster::class, $monster);
        $this->assertSame('Orc', $monster->getName());
        $this->assertSame(50, $monster->getHealth()->getValue());
        $this->assertSame(15, $monster->getAttackPower());
        $this->assertSame(25, $monster->getExperienceReward());
    }

    /**
     * Test that the Dragon factory method returns a properly initialized Monster.
     */
    public function testCreateDragon(): void
    {
        $monster = $this->factory->createDragon();

        // Assert that the monster is correctly configured
        $this->assertInstanceOf(Monster::class, $monster);
        $this->assertSame('Dragon', $monster->getName());
        $this->assertSame(100, $monster->getHealth()->getValue());
        $this->assertSame(30, $monster->getAttackPower());
        $this->assertSame(100, $monster->getExperienceReward());
    }
}
