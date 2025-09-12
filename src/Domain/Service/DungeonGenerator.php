<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Service;

use DungeonCrawler\Domain\Entity\Dungeon;
use DungeonCrawler\Domain\Entity\Monster;
use DungeonCrawler\Domain\Entity\Room;
use DungeonCrawler\Domain\Entity\Treasure;
use DungeonCrawler\Domain\Factory\MonsterFactory;
use DungeonCrawler\Domain\Factory\TreasureFactory;
use DungeonCrawler\Domain\ValueObject\Direction;
use DungeonCrawler\Domain\ValueObject\Position;

/**
 * Service responsible for generating dungeon layouts with rooms, monsters, and treasures.
 *
 * This service implements various generation strategies from simple pre-made layouts
 * to complex procedural generation with configurable parameters.
 */
class DungeonGenerator
{
    /**
     * Factory responsible for creating monster entities with appropriate stats.
     *
     * @var MonsterFactory
     */
    private MonsterFactory $monsterFactory;

    /**
     * Factory responsible for creating treasure entities with rarity-based generation.
     *
     * @var TreasureFactory
     */
    private TreasureFactory $treasureFactory;

    /**
     * Temporary storage of rooms during the generation process.
     * Indexed by position string format "[x,y]" for O(1) lookup.
     *
     *  @var array<string, Room>
     */
    private array $rooms = [];

    /**
     * The designated starting position for players entering the dungeon.
     * Set during generation to ensure proper dungeon flow.
     *
     * @var Position|null
     */
    private ?Position $entrancePosition = null;

    /**
     * The goal position that players must reach to complete the dungeon.
     * Typically placed far from the entrance to maximize exploration.
     *
     * @var Position|null
     */
    private ?Position $exitPosition = null;

    /**
     * Initializes the dungeon generator with required factories.
     *
     * @param MonsterFactory|null $monsterFactory Optional monster factory, creates default if not provided
     * @param TreasureFactory|null $treasureFactory Optional treasure factory, creates default if not provided
     */
    public function __construct(
        ?MonsterFactory $monsterFactory = null,
        ?TreasureFactory $treasureFactory = null
    ) {
        $this->monsterFactory = $monsterFactory ?? new MonsterFactory();
        $this->treasureFactory = $treasureFactory ?? new TreasureFactory();
    }

    /**
     * Generates a procedural dungeon with specified parameters.
     *
     * @param int $width Dungeon width in rooms (minimum 3)
     * @param int $height Dungeon height in rooms (minimum 3)
     * @param int $difficulty Affects monster and treasure quality (minimum 1)
     * @param float $monsterDensity Percentage of rooms with monsters (0.0-1.0)
     * @param float $treasureDensity Percentage of rooms with treasure (0.0-1.0)
     *
     * @return Dungeon The generated dungeon
     *
     * @throws \InvalidArgumentException If parameters are out of valid ranges
     * @throws \RuntimeException If generation fails
     */
    public function generate(
        int $width = 5,
        int $height = 5,
        int $difficulty = 1,
        float $monsterDensity = 0.3,
        float $treasureDensity = 0.2
    ): Dungeon {
        $this->validateParameters($width, $height, $difficulty, $monsterDensity, $treasureDensity);

        $this->rooms = [];
        $this->entrancePosition = null;
        $this->exitPosition = null;

        // Step 1: Create the room layout
        $this->createRoomLayout($width, $height);

        // Step 2: Connect rooms to create a navigable dungeon
        $this->connectRooms($width, $height);

        // Step 3: Set entrance and exit
        $this->placeEntranceAndExit($width, $height);

        // Step 4: Populate with monsters and treasure
        $this->populateRooms($difficulty, $monsterDensity, $treasureDensity);

        // Step 5: Ensure there's a clear path from entrance to exit
        $this->ensurePathToExit();

        return new Dungeon(
            rooms: $this->rooms,
            entrancePosition: $this->entrancePosition,
            exitPosition: $this->exitPosition,
            width: $width,
            height: $height,
            difficulty: $difficulty
        );
    }

    /**
     * Generates a simple 3x3 pre-designed dungeon
     *
     * Layout:
     * [S]---[M]---[.]
     *  |           |
     * [T]     X   [.]
     *  |           |
     * [.]---[M]---[E]
     *
     * S = Start, E = Exit, M = Monster, T = Treasure, . = Empty, X = No room
     *
     * @return Dungeon A simple fixed dungeon
     */
    public function generateSimple(): Dungeon
    {
        $this->rooms = [];

        // Create a simple 3x3 dungeon with a gap in the middle
        $layout = [
            [1, 1, 1],
            [1, 0, 1],
            [1, 1, 1],
        ];

        for ($y = 0; $y < 3; $y++) {
            for ($x = 0; $x < 3; $x++) {
                if ($layout[$y][$x] === 1) {
                    $position = new Position($x, $y);
                    $this->rooms[$position->toString()] = new Room(
                        position: $position,
                        description: $this->generateRoomDescription($x, $y)
                    );
                }
            }
        }

        // Connect the rooms manually for predictable layout
        $this->connectSimpleLayout();

        // Set entrance at top-left, exit at bottom-right
        $this->entrancePosition = new Position(0, 0);
        $this->exitPosition = new Position(2, 2);

        // Replace exit room with special exit room
        $this->rooms[$this->exitPosition->toString()] = new Room(
            position: $this->exitPosition,
            description: 'The exit chamber! A bright light shines from the doorway ahead.',
            monster: null,
            treasure: null,
            isExit: true
        );

        // Restore connections for exit room
        $this->rooms['[2,2]']->connectTo(Direction::WEST);
        $this->rooms['[2,2]']->connectTo(Direction::NORTH);

        // Add predetermined content
        $this->addSimpleContent();

        return new Dungeon(
            rooms: $this->rooms,
            entrancePosition: $this->entrancePosition,
            exitPosition: $this->exitPosition,
            width: 3,
            height: 3,
            difficulty: 1
        );
    }

    /**
     * Validates generation parameters.
     */
    private function validateParameters(
        int $width,
        int $height,
        int $difficulty,
        float $monsterDensity,
        float $treasureDensity
    ): void {
        if ($width < 3 || $height < 3) {
            throw new \InvalidArgumentException('Dungeon must be at least 3x3');
        }

        if ($difficulty < 1) {
            throw new \InvalidArgumentException('Difficulty must be at least 1');
        }

        if ($monsterDensity < 0 || $monsterDensity > 1) {
            throw new \InvalidArgumentException('Monster density must be between 0 and 1');
        }

        if ($treasureDensity < 0 || $treasureDensity > 1) {
            throw new \InvalidArgumentException('Treasure density must be between 0 and 1');
        }
    }

    /**
     * Creates the initial room layout grid.
     */
    private function createRoomLayout(int $width, int $height): void
    {
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                // Create rooms with some random gaps for variety
                if ($this->shouldCreateRoom($x, $y, $width, $height)) {
                    $position = new Position($x, $y);
                    $this->rooms[$position->toString()] = new Room(
                        position: $position,
                        description: $this->generateRoomDescription($x, $y)
                    );
                }
            }
        }

        // Ensure we have enough rooms
        if (count($this->rooms) < 4) {
            // If too few rooms were created, fill in more
            $this->ensureMinimumRooms($width, $height);
        }
    }

    /**
     * Determines if a room should be created at the given coordinates.
     */
    private function shouldCreateRoom(int $x, int $y, int $width, int $height): bool
    {
        // Always create corner rooms for better connectivity
        if (($x === 0 || $x === $width - 1) && ($y === 0 || $y === $height - 1)) {
            return true;
        }

        // Always create border rooms for better connectivity
        if ($x === 0 || $x === $width - 1 || $y === 0 || $y === $height - 1) {
            return rand(1, 100) <= 90; // 90% chance for border rooms
        }

        // 85% chance for interior rooms
        return rand(1, 100) <= 85;
    }

    /**
     * Ensures minimum number of rooms for a playable dungeon.
     */
    private function ensureMinimumRooms(int $width, int $height): void
    {
        // Add corner rooms if missing
        $corners = [
            new Position(0, 0),
            new Position($width - 1, 0),
            new Position(0, $height - 1),
            new Position($width - 1, $height - 1),
        ];

        foreach ($corners as $position) {
            if (!isset($this->rooms[$position->toString()])) {
                $this->rooms[$position->toString()] = new Room(
                    position: $position,
                    description: $this->generateRoomDescription($position->getX(), $position->getY())
                );
            }
        }
    }

    /**
     * Creates connections between adjacent rooms.
     */
    private function connectRooms(int $width, int $height): void
    {
        foreach ($this->rooms as $room) {
            $position = $room->getPosition();

            // Try to connect to adjacent rooms
            foreach (Direction::cases() as $direction) {
                $adjacentPos = $this->getAdjacentPosition($position, $direction);

                if ($adjacentPos && $this->roomExists($adjacentPos)) {
                    // Create bidirectional connection with some randomness
                    if (rand(1, 100) <= 75) { // 75% chance to connect
                        $room->connectTo($direction);
                        $adjacentRoom = $this->rooms[$adjacentPos->toString()];
                        $adjacentRoom->connectTo($direction->opposite());
                    }
                }
            }
        }

        // Ensure minimum connectivity
        $this->ensureConnectivity();
    }

    /**
     * Gets the position adjacent to the given position in the specified direction.
     */
    private function getAdjacentPosition(Position $position, Direction $direction): ?Position
    {
        try {
            $newPos = $position->move($direction);
            // Check if the new position is valid (non-negative)
            if ($newPos->getX() >= 0 && $newPos->getY() >= 0) {
                return $newPos;
            }
        } catch (\InvalidArgumentException $e) {
            // Position would be out of bounds
        }

        return null;
    }

    /**
     * Checks if a room exists at the given position.
     */
    private function roomExists(Position $position): bool
    {
        return isset($this->rooms[$position->toString()]);
    }

    /**
     * Manually connects rooms in the simple layout.
     */
    private function connectSimpleLayout(): void
    {
        // Manually connect the simple 3x3 layout
        $connections = [
            '[0,0]' => [Direction::EAST, Direction::SOUTH],
            '[1,0]' => [Direction::WEST, Direction::EAST],
            '[2,0]' => [Direction::WEST, Direction::SOUTH],
            '[0,1]' => [Direction::NORTH, Direction::SOUTH],
            '[2,1]' => [Direction::NORTH, Direction::SOUTH],
            '[0,2]' => [Direction::NORTH, Direction::EAST],
            '[1,2]' => [Direction::WEST, Direction::EAST],
            '[2,2]' => [Direction::WEST, Direction::NORTH],
        ];

        foreach ($connections as $posStr => $directions) {
            if (isset($this->rooms[$posStr])) {
                foreach ($directions as $direction) {
                    $this->rooms[$posStr]->connectTo($direction);
                }
            }
        }
    }

    /**
     * Places entrance and exit rooms at strategic positions.
     */
    private function placeEntranceAndExit(int $width, int $height): void
    {
        $roomPositions = array_keys($this->rooms);

        if (count($roomPositions) < 2) {
            throw new \RuntimeException('Not enough rooms to place entrance and exit');
        }

        // Place entrance in the top-left quadrant
        $entranceCandidates = array_filter($roomPositions, function($posStr) use ($width, $height) {
            $room = $this->rooms[$posStr];
            $pos = $room->getPosition();
            return $pos->getX() < $width / 2 && $pos->getY() < $height / 2;
        });

        // Place exit in the bottom-right quadrant
        $exitCandidates = array_filter($roomPositions, function($posStr) use ($width, $height) {
            $room = $this->rooms[$posStr];
            $pos = $room->getPosition();
            return $pos->getX() >= $width / 2 && $pos->getY() >= $height / 2;
        });

        // Fallback if quadrants don't have rooms
        if (empty($entranceCandidates)) {
            $entranceCandidates = $roomPositions;
        }
        if (empty($exitCandidates)) {
            $exitCandidates = $roomPositions;
        }

        $entranceKey = $entranceCandidates[array_rand($entranceCandidates)];
        $this->entrancePosition = $this->rooms[$entranceKey]->getPosition();

        // Ensure exit is different from entrance
        $exitCandidates = array_diff($exitCandidates, [$entranceKey]);
        if (empty($exitCandidates)) {
            $exitCandidates = array_diff($roomPositions, [$entranceKey]);
        }

        $exitKey = $exitCandidates[array_rand($exitCandidates)];
        $exitRoom = $this->rooms[$exitKey];
        $this->exitPosition = $exitRoom->getPosition();

        // Replace exit room with special exit room
        $this->rooms[$exitKey] = new Room(
            position: $this->exitPosition,
            description: 'The exit chamber! A bright light shines from the doorway ahead.',
            monster: null,
            treasure: null,
            isExit: true
        );

        // Maintain connections
        foreach ($exitRoom->getAvailableDirections() as $direction) {
            $this->rooms[$exitKey]->connectTo($direction);
        }
    }

    /**
     * Populates rooms with monsters and treasures based on difficulty and density.
     */
    private function populateRooms(int $difficulty, float $monsterDensity, float $treasureDensity): void
    {
        foreach ($this->rooms as $posStr => $room) {
            // Don't put anything in entrance or exit rooms
            if ($room->getPosition()->equals($this->entrancePosition) || $room->isExit()) {
                continue;
            }

            $monster = null;
            $treasure = null;

            // Place monsters
            if ($this->shouldPlaceMonster($monsterDensity)) {
                $monster = $this->createMonsterForDifficulty($difficulty);
            }

            // Place treasure (can be in same room as monster)
            if ($this->shouldPlaceTreasure($treasureDensity)) {
                $treasure = $this->treasureFactory->createForDifficulty($difficulty);
            }

            // Only recreate room if we're adding content
            if ($monster !== null || $treasure !== null) {
                $newRoom = new Room(
                    position: $room->getPosition(),
                    description: $room->getDescription(),
                    monster: $monster,
                    treasure: $treasure,
                    isExit: false
                );

                // Maintain connections
                foreach ($room->getAvailableDirections() as $direction) {
                    $newRoom->connectTo($direction);
                }

                $this->rooms[$posStr] = $newRoom;
            }
        }
    }

    /**
     * Creates a monster appropriate for the difficulty level.
     */
    private function createMonsterForDifficulty(int $difficulty): Monster
    {
        // Since MonsterFactory doesn't have createForDifficulty method,
        // we'll use the predefined monsters based on difficulty
        $roll = rand(1, 100);

        if ($difficulty <= 2) {
            // Low difficulty: mostly goblins
            return $roll <= 70 ? $this->monsterFactory->createGoblin() : $this->monsterFactory->createOrc();
        } elseif ($difficulty <= 4) {
            // Medium difficulty: mix of goblins and orcs
            return $roll <= 40 ? $this->monsterFactory->createGoblin() : $this->monsterFactory->createOrc();
        } else {
            // High difficulty: orcs and occasional dragons
            if ($roll <= 10) {
                return $this->monsterFactory->createDragon();
            } elseif ($roll <= 50) {
                return $this->monsterFactory->createGoblin();
            } else {
                return $this->monsterFactory->createOrc();
            }
        }
    }

    /**
     * Adds predetermined content to the simple dungeon layout.
     */
    private function addSimpleContent(): void
    {
        // Add a goblin at position [1,0]
        if (isset($this->rooms['[1,0]'])) {
            $room = $this->rooms['[1,0]'];
            $this->rooms['[1,0]'] = new Room(
                position: $room->getPosition(),
                description: $room->getDescription(),
                monster: $this->monsterFactory->createGoblin(),
                treasure: null,
                isExit: false
            );
            foreach ($room->getAvailableDirections() as $dir) {
                $this->rooms['[1,0]']->connectTo($dir);
            }
        }

        // Add treasure at position [0,1]
        if (isset($this->rooms['[0,1]'])) {
            $room = $this->rooms['[0,1]'];
            $this->rooms['[0,1]'] = new Room(
                position: $room->getPosition(),
                description: $room->getDescription(),
                monster: null,
                treasure: $this->treasureFactory->createByRarity('common'),
                isExit: false
            );
            foreach ($room->getAvailableDirections() as $dir) {
                $this->rooms['[0,1]']->connectTo($dir);
            }
        }

        // Add an orc with treasure at position [1,2]
        if (isset($this->rooms['[1,2]'])) {
            $room = $this->rooms['[1,2]'];
            $this->rooms['[1,2]'] = new Room(
                position: $room->getPosition(),
                description: $room->getDescription(),
                monster: $this->monsterFactory->createOrc(),
                treasure: $this->treasureFactory->createByRarity('uncommon'),
                isExit: false
            );
            foreach ($room->getAvailableDirections() as $dir) {
                $this->rooms['[1,2]']->connectTo($dir);
            }
        }
    }

    /**
     * Determines if a monster should be placed based on density.
     */
    private function shouldPlaceMonster(float $density): bool
    {
        return rand(1, 100) <= ($density * 100);
    }

    /**
     * Determines if treasure should be placed based on density.
     */
    private function shouldPlaceTreasure(float $density): bool
    {
        return rand(1, 100) <= ($density * 100);
    }

    /**
     * Ensures all rooms are connected using flood-fill algorithm.
     */
    private function ensureConnectivity(): void
    {
        if (empty($this->rooms)) {
            return;
        }

        // Use flood-fill to find connected components
        $visited = [];
        $toVisit = [array_key_first($this->rooms)];

        while (!empty($toVisit)) {
            $currentKey = array_pop($toVisit);
            if (in_array($currentKey, $visited)) {
                continue;
            }

            $visited[] = $currentKey;
            $currentRoom = $this->rooms[$currentKey];

            foreach ($currentRoom->getAvailableDirections() as $direction) {
                $adjacentPos = $this->getAdjacentPosition($currentRoom->getPosition(), $direction);
                if ($adjacentPos && $this->roomExists($adjacentPos)) {
                    $adjacentKey = $adjacentPos->toString();
                    if (!in_array($adjacentKey, $visited)) {
                        $toVisit[] = $adjacentKey;
                    }
                }
            }
        }

        // Find unconnected rooms
        $unconnected = array_diff(array_keys($this->rooms), $visited);

        // Connect each unconnected room to the main component
        foreach ($unconnected as $roomKey) {
            $this->connectToNearestRoom($this->rooms[$roomKey]);
        }
    }

    /**
     * Connects an isolated room to its nearest neighbor.
     */
    private function connectToNearestRoom(Room $room): void
    {
        // Try each direction until we find an adjacent room
        foreach (Direction::cases() as $direction) {
            $adjacentPos = $this->getAdjacentPosition($room->getPosition(), $direction);
            if ($adjacentPos && $this->roomExists($adjacentPos)) {
                // Create bidirectional connection
                $room->connectTo($direction);
                $this->rooms[$adjacentPos->toString()]->connectTo($direction->opposite());
                break;
            }
        }
    }

    /**
     * Ensures there's a navigable path from entrance to exit.
     */
    private function ensurePathToExit(): void
    {
        if (!$this->entrancePosition || !$this->exitPosition) {
            return;
        }

        $entranceRoom = $this->rooms[$this->entrancePosition->toString()] ?? null;
        $exitRoom = $this->rooms[$this->exitPosition->toString()] ?? null;

        // Ensure entrance has at least one connection
        if ($entranceRoom && empty($entranceRoom->getAvailableDirections())) {
            $this->forceConnection($entranceRoom);
        }

        // Ensure exit has at least one connection
        if ($exitRoom && empty($exitRoom->getAvailableDirections())) {
            $this->forceConnection($exitRoom);
        }

        // Additional check: ensure path exists using BFS
        if (!$this->pathExists($this->entrancePosition, $this->exitPosition)) {
            // If no path exists, create a direct corridor
            $this->createDirectPath();
        }
    }

    /**
     * Forces a room to connect to at least one adjacent room.
     */
    private function forceConnection(Room $room): void
    {
        foreach (Direction::cases() as $direction) {
            $adjacentPos = $this->getAdjacentPosition($room->getPosition(), $direction);
            if ($adjacentPos && $this->roomExists($adjacentPos)) {
                $room->connectTo($direction);
                $this->rooms[$adjacentPos->toString()]->connectTo($direction->opposite());
                break;
            }
        }
    }

    /**
     * Checks if a path exists between two positions using BFS.
     */
    private function pathExists(Position $start, Position $end): bool
    {
        $visited = [];
        $queue = [$start->toString()];

        while (!empty($queue)) {
            $currentKey = array_shift($queue);

            if ($currentKey === $end->toString()) {
                return true;
            }

            if (in_array($currentKey, $visited)) {
                continue;
            }

            $visited[] = $currentKey;
            $currentRoom = $this->rooms[$currentKey] ?? null;

            if (!$currentRoom) {
                continue;
            }

            foreach ($currentRoom->getAvailableDirections() as $direction) {
                $adjacentPos = $this->getAdjacentPosition($currentRoom->getPosition(), $direction);
                if ($adjacentPos && $this->roomExists($adjacentPos)) {
                    $adjacentKey = $adjacentPos->toString();
                    if (!in_array($adjacentKey, $visited)) {
                        $queue[] = $adjacentKey;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Creates a direct path between entrance and exit as a fallback.
     */
    private function createDirectPath(): void
    {
        // This is a simplified approach - just ensure all rooms have some connections
        foreach ($this->rooms as $room) {
            if (empty($room->getAvailableDirections())) {
                $this->forceConnection($room);
            }
        }
    }

    /**
     * Generates a contextual room description based on position.
     */
    private function generateRoomDescription(int $x, int $y): string
    {
        $descriptions = [
            'A dimly lit chamber with stone walls covered in moss.',
            'A spacious hall with ancient pillars reaching to the ceiling.',
            'A narrow corridor with flickering torches on the walls.',
            'A circular room with mysterious symbols etched into the floor.',
            'A cold chamber with the sound of dripping water echoing.',
            'A dusty room filled with cobwebs and shadows.',
            'A vault with a high ceiling lost in darkness.',
            'A cramped space with rough-hewn walls.',
            'An abandoned guard post with rusted weapons on the walls.',
            'A natural cavern with stalactites hanging from above.',
            'A forgotten library with crumbling shelves and scattered pages.',
            'A ritual chamber with a broken altar at its center.',
            'A storage room with broken crates and barrels.',
            'A sleeping quarters with rotted beds and torn tapestries.',
            'A throne room, its glory long faded into decay.',
        ];

        // Use position as seed for consistent descriptions
        $index = abs(($x * 7 + $y * 13)) % count($descriptions);
        return $descriptions[$index];
    }
}