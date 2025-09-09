<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use DungeonCrawler\Domain\Entity\Room;
use DungeonCrawler\Domain\Entity\Monster;
use DungeonCrawler\Domain\Entity\Treasure;
use DungeonCrawler\Domain\ValueObject\Direction;
use DungeonCrawler\Domain\ValueObject\Position;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;

/**
 * Unit tests for the Room entity.
 *
 * This test suite verifies the correct behavior of the Room class including:
 * - Construction and property initialization
 * - Connection management
 * - Visitation state
 * - Monster and treasure presence and removal
 * - Room state checks such as emptiness and exit detection
 *
 * @covers \DungeonCrawler\Domain\Entity\Room
 */
final class RoomTest extends TestCase
{
    private Room $room;

    /**
     * Setup a default Room instance for tests.
     */
    protected function setUp(): void
    {
        $this->room = new Room(
            position: new Position(1, 2),
            description: 'Test room',
            monster: null,
            treasure: null,
            isExit: false
        );
    }

    /**
     * Tests the constructor initializes all properties correctly.
     */
    public function testConstructorInitializesProperties(): void
    {
        $this->assertInstanceOf(UuidInterface::class, $this->room->getId());
        $this->assertSame(1, $this->room->getPosition()->getX());
        $this->assertSame(2, $this->room->getPosition()->getY());
        $this->assertSame('Test room', $this->room->getDescription());
        $this->assertNull($this->room->getMonster());
        $this->assertNull($this->room->getTreasure());
        $this->assertFalse($this->room->isExit());
        $this->assertFalse($this->room->isVisited());
        $this->assertFalse($this->room->hasMonster());
        $this->assertFalse($this->room->hasTreasure());
        $this->assertTrue($this->room->isEmpty());
    }

    /**
     * Tests that a generated description is assigned if none is provided.
     */
    public function testGenerateDescriptionIsUsedWhenDescriptionEmpty(): void
    {
        $room = new Room(
            position: new Position(0, 0),
            description: '',
            monster: null,
            treasure: null,
            isExit: false
        );

        $this->assertNotEmpty($room->getDescription());
    }

    /**
     * Tests connection creation and detection.
     */
    public function testConnectToAndHasConnection(): void
    {
        // Initially no connection to NORTH
        $this->assertFalse($this->room->hasConnection(Direction::NORTH));

        // Connect to NORTH and verify
        $this->room->connectTo(Direction::NORTH);
        $this->assertTrue($this->room->hasConnection(Direction::NORTH));
    }

    /**
     * Tests retrieval of all available (connected) directions.
     */
    public function testGetAvailableDirections(): void
    {
        $this->room->connectTo(Direction::EAST);
        $this->room->connectTo(Direction::WEST);

        $directions = $this->room->getAvailableDirections();

        $this->assertContains(Direction::EAST, $directions);
        $this->assertContains(Direction::WEST, $directions);
        $this->assertNotContains(Direction::NORTH, $directions);
        $this->assertNotContains(Direction::SOUTH, $directions);
    }

    /**
     * Tests that entering the room marks it as visited.
     */
    public function testEnterMarksRoomAsVisited(): void
    {
        $this->assertFalse($this->room->isVisited());
        $this->room->enter();
        $this->assertTrue($this->room->isVisited());
    }

    /**
     * Tests that removeMonster clears the monster from the room.
     */
    public function testRemoveMonsterRemovesIt(): void
    {
        $monster = $this->createMock(Monster::class);
        $room = new Room(
            position: new Position(0, 0),
            monster: $monster
        );

        $this->assertNotNull($room->getMonster());
        $room->removeMonster();
        $this->assertNull($room->getMonster());
    }

    /**
     * Tests that removeTreasure returns the treasure and removes it from the room.
     */
    public function testRemoveTreasureRemovesAndReturnsTreasure(): void
    {
        $treasure = $this->createMock(Treasure::class);
        $room = new Room(
            position: new Position(0, 0),
            treasure: $treasure
        );

        $this->assertSame($treasure, $room->getTreasure());

        $removed = $room->removeTreasure();

        $this->assertSame($treasure, $removed);
        $this->assertNull($room->getTreasure());
    }

    /**
     * Tests that hasMonster returns true only if the monster exists and is alive.
     */
    public function testHasMonsterReturnsTrueWhenMonsterIsAlive(): void
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('isAlive')->willReturn(true);

        $room = new Room(
            position: new Position(0, 0),
            monster: $monster
        );

        $this->assertTrue($room->hasMonster());
    }

    /**
     * Tests that hasMonster returns false when no monster or monster is dead.
     */
    public function testHasMonsterReturnsFalseWhenNoMonsterOrDead(): void
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('isAlive')->willReturn(false);

        $roomWithDeadMonster = new Room(
            position: new Position(0, 0),
            monster: $monster
        );

        $roomWithNoMonster = new Room(
            position: new Position(0, 0),
            monster: null
        );

        $this->assertFalse($roomWithDeadMonster->hasMonster());
        $this->assertFalse($roomWithNoMonster->hasMonster());
    }

    /**
     * Tests that hasTreasure returns true if a treasure is present.
     */
    public function testHasTreasureReturnsTrueWhenTreasurePresent(): void
    {
        $treasure = $this->createMock(Treasure::class);
        $room = new Room(
            position: new Position(0, 0),
            treasure: $treasure
        );

        $this->assertTrue($room->hasTreasure());
    }

    /**
     * Tests that hasTreasure returns false when no treasure is present.
     */
    public function testHasTreasureReturnsFalseWhenNoTreasure(): void
    {
        $room = new Room(
            position: new Position(0, 0),
            treasure: null
        );

        $this->assertFalse($room->hasTreasure());
    }

    /**
     * Tests isEmpty returns true only when there is no monster, no treasure, and the room is not an exit.
     */
    public function testIsEmptyReturnsTrueOnlyWhenNoMonsterNoTreasureAndNotExit(): void
    {
        // Empty room (no monster, no treasure, not exit)
        $room = new Room(
            position: new Position(0, 0),
            monster: null,
            treasure: null,
            isExit: false
        );
        $this->assertTrue($room->isEmpty());

        // Room with alive monster
        $monster = $this->createMock(Monster::class);
        $monster->method('isAlive')->willReturn(true);
        $roomWithMonster = new Room(
            position: new Position(0, 0),
            monster: $monster
        );
        $this->assertFalse($roomWithMonster->isEmpty());

        // Room with treasure
        $treasure = $this->createMock(Treasure::class);
        $roomWithTreasure = new Room(
            position: new Position(0, 0),
            treasure: $treasure
        );
        $this->assertFalse($roomWithTreasure->isEmpty());

        // Room that is an exit
        $exitRoom = new Room(
            position: new Position(0, 0),
            isExit: true
        );
        $this->assertFalse($exitRoom->isEmpty());
    }
}
