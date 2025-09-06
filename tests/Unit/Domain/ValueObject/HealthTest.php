<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use DungeonCrawler\Domain\ValueObject\Health;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Health value object
 *
 * This test suite verifies the behavior of the Health value object, ensuring:
 * - Proper instantiation with valid and invalid values
 * - Immutability (methods return new instances rather than modifying state)
 * - Business logic correctness (damage reduction, healing, boundaries)
 * - Value object equality semantics
 * - String representation for debugging
 *
 * The Health value object is critical to game mechanics as it tracks
 * the life points of both players and monsters. These tests ensure
 * the reliability of combat and damage calculations.
 *
 * @covers     \DungeonCrawler\Domain\ValueObject\Health
 * @author     TooCodeSavvy
 */
final class HealthTest extends TestCase
{
    /**
     * Test that Health can be instantiated with valid values
     *
     * This test verifies the basic constructor functionality and
     * the accessor methods return the correct values. The percentage
     * calculation is also validated.
     *
     * @test
     * @return void
     */
    public function testCanCreateHealthWithValidValues(): void
    {
        // Arrange & Act: Create a Health instance with 75/100 HP
        $health = new Health(75, 100);

        // Assert: Verify all getters return expected values
        $this->assertSame(75, $health->getValue(), 'Current health should be 75');
        $this->assertSame(100, $health->getMax(), 'Max health should be 100');
        $this->assertSame(75.0, $health->getPercentage(), 'Health percentage should be 75.0%');
    }

    /**
     * Test the factory method for creating full health
     *
     * The Health::full() static factory method is a convenience method
     * for creating entities at maximum health. This is commonly used
     * when spawning new monsters or starting a new game.
     *
     * @test
     * @return void
     */
    public function testCreateFullHealth(): void
    {
        // Arrange & Act: Create Health at maximum using factory method
        $health = Health::full(100);

        // Assert: Verify health is at maximum
        $this->assertSame(100, $health->getValue(), 'Current health should equal max');
        $this->assertSame(100, $health->getMax(), 'Max health should be 100');
        $this->assertTrue($health->isFull(), 'Health should report as full');
        $this->assertFalse($health->isDead(), 'Full health should not be dead');
    }

    /**
     * Test that reduce() method maintains immutability
     *
     * Value objects must be immutable. This test ensures that calling
     * reduce() returns a new Health instance rather than modifying
     * the existing one. This is crucial for maintaining predictable
     * state in the game engine.
     *
     * @test
     * @return void
     */
    public function testReduceCreatesNewInstance(): void
    {
        // Arrange: Create initial health at full
        $health = new Health(100, 100);

        // Act: Apply damage
        $damaged = $health->reduce(30);

        // Assert: Original instance unchanged, new instance has damage
        $this->assertNotSame($health, $damaged, 'reduce() should return new instance');
        $this->assertSame(100, $health->getValue(), 'Original health should be unchanged');
        $this->assertSame(70, $damaged->getValue(), 'New instance should have reduced health');
    }

    /**
     * Test that health cannot go below zero
     *
     * This boundary test ensures that excessive damage doesn't result
     * in negative health values, which could cause display issues or
     * logic errors in the game. Health should be clamped at 0.
     *
     * @test
     * @return void
     */
    public function testHealthCannotGoNegative(): void
    {
        // Arrange: Create health with low HP
        $health = new Health(10, 100);

        // Act: Apply excessive damage (more than current health)
        $damaged = $health->reduce(50);

        // Assert: Health is clamped at 0, not negative
        $this->assertSame(0, $damaged->getValue(), 'Health should be clamped at 0');
        $this->assertTrue($damaged->isDead(), 'Zero health should be considered dead');
    }

    /**
     * Test that healing cannot exceed maximum health
     *
     * This boundary test ensures that healing is capped at the maximum
     * health value. This prevents "overhealing" which could unbalance
     * game mechanics or cause display issues.
     *
     * @test
     * @return void
     */
    public function testHealingCappedAtMax(): void
    {
        // Arrange: Create health below maximum
        $health = new Health(80, 100);

        // Act: Apply excessive healing (more than needed to reach max)
        $healed = $health->heal(50);

        // Assert: Health is capped at maximum
        $this->assertSame(100, $healed->getValue(), 'Health should be capped at max');
        $this->assertTrue($healed->isFull(), 'Max health should report as full');
    }

    /**
     * Test constructor validation for negative max health
     *
     * The constructor should reject invalid max health values.
     * A negative or zero max health makes no logical sense in the game
     * context and would cause division by zero in percentage calculations.
     *
     * @test
     * @return void
     */
    public function testThrowsExceptionForNegativeMax(): void
    {
        // Arrange: Set up expectation for exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Max health must be positive');

        // Act & Assert: Attempt to create Health with invalid max
        new Health(50, -10);
    }

    /**
     * Test that reduce() validates damage parameter
     *
     * Negative damage would effectively be healing, which violates
     * the single responsibility principle. The reduce() method should
     * only reduce health, never increase it.
     *
     * @test
     * @return void
     */
    public function testThrowsExceptionForNegativeDamage(): void
    {
        // Arrange: Create valid health and set exception expectation
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Damage cannot be negative');

        $health = new Health(50, 100);

        // Act & Assert: Attempt to reduce with negative damage
        $health->reduce(-10);
    }

    /**
     * Test value object equality semantics
     *
     * Value objects should be compared by their values, not by reference.
     * Two Health instances with the same current and max values should
     * be considered equal, even if they are different object instances.
     *
     * @test
     * @return void
     */
    public function testEquality(): void
    {
        // Arrange: Create three Health instances
        $health1 = new Health(75, 100);
        $health2 = new Health(75, 100);  // Same values as health1
        $health3 = new Health(50, 100);  // Different current value

        // Act & Assert: Test equality comparisons
        $this->assertTrue(
            $health1->equals($health2),
            'Health instances with same values should be equal'
        );
        $this->assertFalse(
            $health1->equals($health3),
            'Health instances with different values should not be equal'
        );
    }

    /**
     * Test string representation for debugging
     *
     * The __toString() method provides a human-readable representation
     * of the health state. This is useful for debugging, logging, and
     * potentially for display in the game UI.
     *
     * @test
     * @return void
     */
    public function testStringRepresentation(): void
    {
        // Arrange & Act: Create Health and convert to string
        $health = new Health(75, 100);

        // Assert: String format is "current/max"
        $this->assertSame(
            '75/100',
            (string) $health,
            'String representation should be in format "current/max"'
        );
    }

    /**
     * @test
     * @dataProvider healthPercentageProvider
     *
     * Test percentage calculations with various health values
     *
     * This parameterized test verifies that percentage calculations
     * are accurate across different health ratios. This is important
     * for health bar displays and AI decision-making.
     *
     * @param int $current Current health value
     * @param int $max Maximum health value
     * @param float $expectedPercentage Expected percentage result
     * @return void
     */
    public function testHealthPercentageCalculations(
        int $current,
        int $max,
        float $expectedPercentage
    ): void {
        // Arrange & Act
        $health = new Health($current, $max);

        // Assert
        $this->assertEqualsWithDelta(
            $expectedPercentage,
            $health->getPercentage(),
            0.01,
            "Percentage calculation for {$current}/{$max} should be {$expectedPercentage}%"
        );
    }

    /**
     * Data provider for health percentage test cases
     *
     * Provides various health ratios and their expected percentages
     * to ensure the percentage calculation works correctly across
     * different scenarios.
     *
     * @return array<string, array{current: int, max: int, expectedPercentage: float}>
     */
    public static function healthPercentageProvider(): array
    {
        return [
            'full health' => [
                'current' => 100,
                'max' => 100,
                'expectedPercentage' => 100.0,
            ],
            'half health' => [
                'current' => 50,
                'max' => 100,
                'expectedPercentage' => 50.0,
            ],
            'quarter health' => [
                'current' => 25,
                'max' => 100,
                'expectedPercentage' => 25.0,
            ],
            'near death' => [
                'current' => 1,
                'max' => 100,
                'expectedPercentage' => 1.0,
            ],
            'dead' => [
                'current' => 0,
                'max' => 100,
                'expectedPercentage' => 0.0,
            ],
            'non-standard max' => [
                'current' => 33,
                'max' => 150,
                'expectedPercentage' => 22.0,
            ],
        ];
    }

    /**
     * Test edge case: Creating health at exactly zero
     *
     * This edge case ensures that a character can be created in a
     * dead state, which might be needed for certain game scenarios
     * or loading saved games where the player has died.
     *
     * @test
     * @return void
     */
    public function testCanCreateHealthAtZero(): void
    {
        // Arrange & Act: Create Health at 0
        $health = new Health(0, 100);

        // Assert: Health is valid but dead
        $this->assertSame(0, $health->getValue());
        $this->assertTrue($health->isDead(), 'Zero health should be dead');
        $this->assertFalse($health->isFull(), 'Zero health should not be full');
        $this->assertSame(0.0, $health->getPercentage());
    }

    /**
     * Test that current health equals max in boundary case
     *
     * This test ensures that the validation logic correctly handles
     * the case where current health exactly equals max health,
     * which should be valid.
     *
     * @test
     * @return void
     */
    public function testCurrentCanEqualMax(): void
    {
        // This should not throw an exception
        $health = new Health(100, 100);

        $this->assertSame(100, $health->getValue());
        $this->assertSame(100, $health->getMax());
        $this->assertTrue($health->isFull());
    }
}