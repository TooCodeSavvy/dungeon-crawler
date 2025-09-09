<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use DungeonCrawler\Domain\Entity\Player;
use DungeonCrawler\Domain\ValueObject\Health;
use DungeonCrawler\Domain\ValueObject\Position;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;

/**
 * Unit tests for the Player entity.
 *
 * Tests cover:
 * - Construction and validation of Player properties.
 * - Movement functionality (position updates).
 * - Damage handling and healing.
 * - Attack power range with variance.
 * - Experience points gain and validation.
 * - Alive/dead status determination.
 *
 * @covers \DungeonCrawler\Domain\Entity\Player
 */
final class PlayerTest extends TestCase
{
    /**
     * @var Player The Player instance used in tests.
     */
    private Player $player;

    /**
     * Sets up the Player instance before each test.
     */
    protected function setUp(): void
    {
        $this->player = new Player(
            name: 'Hero',
            health: Health::full(100),
            position: new Position(0, 0),
            attackPower: 20
        );
    }

    /**
     * Tests that the constructor correctly sets Player properties.
     */
    public function testConstructorSetsPropertiesCorrectly(): void
    {
        $this->assertSame('Hero', $this->player->getName());
        $this->assertSame(100, $this->player->getHealth()->getValue());
        $this->assertSame(20, $this->player->getAttackPower());
        $this->assertInstanceOf(UuidInterface::class, $this->player->getId());
        $this->assertSame(0, $this->player->getPosition()->getX());
        $this->assertSame(0, $this->player->getPosition()->getY());
        $this->assertSame(0, $this->player->getExperiencePoints());
    }

    /**
     * Tests that an empty name throws an InvalidArgumentException.
     */
    public function testInvalidNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Player('', Health::full(100), new Position(0, 0), 10);
    }

    /**
     * Tests that a non-positive attack power throws an InvalidArgumentException.
     */
    public function testInvalidAttackPowerThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Player('Hero', Health::full(100), new Position(0, 0), 0);
    }

    /**
     * Tests that moveTo updates the Player's position correctly.
     */
    public function testMoveToChangesPosition(): void
    {
        $newPosition = new Position(2, 3);
        $this->player->moveTo($newPosition);

        $this->assertSame($newPosition, $this->player->getPosition());
    }

    /**
     * Tests that taking damage reduces the Player's health.
     */
    public function testTakeDamageReducesHealth(): void
    {
        $this->player->takeDamage(30);

        $this->assertSame(70, $this->player->getHealth()->getValue());
    }

    /**
     * Tests that healing increases the Player's health correctly.
     */
    public function testHealIncreasesHealth(): void
    {
        $this->player->takeDamage(50);
        $this->player->heal(30);

        $this->assertSame(80, $this->player->getHealth()->getValue());
    }

    /**
     * Tests that the attack method returns a damage value
     * within the expected variance range.
     */
    public function testAttackReturnsValueWithinExpectedRange(): void
    {
        $min = (int) (20 * 0.8);
        $max = (int) (20 * 1.2);

        for ($i = 0; $i < 10; $i++) {
            $damage = $this->player->attack();
            $this->assertGreaterThanOrEqual($min, $damage);
            $this->assertLessThanOrEqual($max, $damage);
        }
    }

    /**
     * Tests that gaining experience increases the experience points correctly.
     */
    public function testGainExperienceIncreasesPoints(): void
    {
        $this->player->gainExperience(10);
        $this->assertSame(10, $this->player->getExperiencePoints());

        $this->player->gainExperience(5);
        $this->assertSame(15, $this->player->getExperiencePoints());
    }

    /**
     * Tests that attempting to gain negative experience throws an exception.
     */
    public function testGainNegativeExperienceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->player->gainExperience(-5);
    }

    /**
     * Tests that isAlive returns false when health is zero.
     */
    public function testIsAliveReturnsFalseWhenHealthIsZero(): void
    {
        $this->player->takeDamage(100);
        $this->assertFalse($this->player->isAlive());
    }

    /**
     * Tests that isAlive returns true when health is above zero.
     */
    public function testIsAliveReturnsTrueWhenHealthIsAboveZero(): void
    {
        $this->player->takeDamage(99);
        $this->assertTrue($this->player->isAlive());
    }
}
