<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use DungeonCrawler\Domain\ValueObject\Direction;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Direction enum.
 *
 * Covers parsing of string inputs into Direction enum cases,
 * verification of the opposite() method logic,
 * and proper exception handling for invalid input.
 *
 * @covers \DungeonCrawler\Domain\ValueObject\Direction
 */
final class DirectionTest extends TestCase
{
    /**
     * Tests that Direction::fromString correctly converts valid input strings
     * (including shorthand and case-insensitive variations) into Direction enums.
     *
     * @param string $input The input string representing a direction.
     * @param Direction $expected The expected Direction enum case.
     *
     * @return void
     */
    #[DataProvider('validDirectionProvider')]
    public function testFromStringReturnsCorrectEnum(string $input, Direction $expected): void
    {
        $result = Direction::fromString($input);
        $this->assertSame($expected, $result);
    }

    /**
     * Tests that Direction::fromString throws an InvalidArgumentException
     * when provided with an invalid direction string.
     *
     * @return void
     */
    public function testFromStringThrowsExceptionOnInvalidInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid direction: up');

        Direction::fromString('up');
    }

    /**
     * Tests that the opposite() method returns the correct opposite Direction.
     *
     * @param Direction $input The original Direction enum case.
     * @param Direction $expectedOpposite The expected opposite Direction enum case.
     *
     * @return void
     */
    #[DataProvider('oppositeDirectionProvider')]
    public function testOppositeReturnsCorrectDirection(Direction $input, Direction $expectedOpposite): void
    {
        $this->assertSame($expectedOpposite, $input->opposite());
    }

    /**
     * Provides valid input strings and their expected Direction enum cases
     * for use in testFromStringReturnsCorrectEnum.
     *
     * @return array<string, array{string, Direction}>
     */
    public static function validDirectionProvider(): array
    {
        return [
            'North (full)' => ['north', Direction::NORTH],
            'South (full)' => ['south', Direction::SOUTH],
            'East (full)'  => ['east', Direction::EAST],
            'West (full)'  => ['west', Direction::WEST],
            'North (short)' => ['n', Direction::NORTH],
            'South (short)' => ['s', Direction::SOUTH],
            'East (short)'  => ['e', Direction::EAST],
            'West (short)'  => ['w', Direction::WEST],
            'Case and whitespace' => ['  NoRtH  ', Direction::NORTH],
        ];
    }

    /**
     * Provides Direction enum cases and their expected opposites
     * for use in testOppositeReturnsCorrectDirection.
     *
     * @return array<string, array{Direction, Direction}>
     */
    public static function oppositeDirectionProvider(): array
    {
        return [
            'North → South' => [Direction::NORTH, Direction::SOUTH],
            'South → North' => [Direction::SOUTH, Direction::NORTH],
            'East → West'   => [Direction::EAST, Direction::WEST],
            'West → East'   => [Direction::WEST, Direction::EAST],
        ];
    }
}
