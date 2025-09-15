<?php
declare(strict_types=1);

namespace DungeonCrawler\Infrastructure\Persistence;

use DungeonCrawler\Domain\Entity\Dungeon;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Entity\Item;
use DungeonCrawler\Domain\Entity\Monster;
use DungeonCrawler\Domain\Entity\Player;
use DungeonCrawler\Domain\Entity\Room;
use DungeonCrawler\Domain\Entity\Treasure;
use DungeonCrawler\Domain\Repository\GameRepositoryInterface;
use DungeonCrawler\Domain\ValueObject\Direction;
use DungeonCrawler\Domain\ValueObject\Health;
use DungeonCrawler\Domain\ValueObject\Position;

/**
 * Repository for saving and loading Game entities as JSON files.
 *
 * Saves are stored under the `data/saves/` directory relative to the project.
 */
class JsonGameRepository implements GameRepositoryInterface
{
    private const SAVE_DIR = __DIR__ . '/../../../data/saves/';

    public function __construct()
    {
        // Ensure save directory exists, create if not
        if (!is_dir(self::SAVE_DIR)) {
            mkdir(self::SAVE_DIR, 0777, true);
        }
    }

    /**
     * Saves the given Game entity as a JSON file.
     *
     * @param Game $game The game to save
     * @param string|null $saveId Optional save ID to update instead of creating new
     * @return string The save ID
     * @throws \RuntimeException If saving fails
     */
    public function save(Game $game, ?string $saveId = null): string
    {
        // If no save ID provided, generate a new one
        $saveId = $saveId ?? uniqid('save_', true);

        $filename = self::SAVE_DIR . $saveId . '.json';
        $data = $this->serialize($game);

        // Add timestamp to the save data
        $data['timestamp'] = time();

        if (file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT)) === false) {
            throw new \RuntimeException("Failed to save game to file: $filename");
        }

        return $saveId;
    }

    /**
     * Deletes a saved game file by save ID.
     *
     * @param string $saveId The save identifier
     * @throws \RuntimeException If deletion fails
     */
    public function delete(string $saveId): void
    {
        $filename = self::SAVE_DIR . $saveId . '.json';

        if (!file_exists($filename)) {
            return; // File doesn't exist, nothing to do
        }

        if (!unlink($filename)) {
            throw new \RuntimeException("Failed to delete save file: $saveId");
        }
    }

    /**
     * Loads a saved Game entity from a JSON file by save ID.
     *
     * @param string $saveId The save identifier
     * @return Game The reconstructed Game entity
     * @throws \RuntimeException|\DateMalformedStringException If loading or parsing fails
     */
    public function load(string $saveId): Game
    {
        $filename = self::SAVE_DIR . $saveId . '.json';

        if (!file_exists($filename)) {
            throw new \RuntimeException("Save file not found: $saveId");
        }

        $json = file_get_contents($filename);
        if ($json === false) {
            throw new \RuntimeException("Failed to read save file: $saveId");
        }

        $data = json_decode($json, true);
        if ($data === null) {
            throw new \RuntimeException("Invalid save file format: $saveId");
        }

        return $this->deserialize($data);
    }

    /**
     * Lists all saved games metadata sorted by most recent save.
     *
     * @return array<string, array{player_name: string, turn: int, saved_at: int}>
     */
    public function listSaves(): array
    {
        $saves = [];
        $files = glob(self::SAVE_DIR . '*.json');

        foreach ($files as $file) {
            $saveId = basename($file, '.json');

            try {
                $data = json_decode(file_get_contents($file), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Skip corrupted files
                    continue;
                }

                $saves[$saveId] = [
                    'player_name' => $data['player']['name'] ?? 'Unknown',
                    'turn' => $data['turn'] ?? 0,
                    'saved_at' => filemtime($file)
                ];
            } catch (\Throwable $e) {
                // Skip files that can't be read
                continue;
            }
        }

        // Sort descending by saved_at timestamp
        uasort($saves, fn($a, $b) => $b['saved_at'] <=> $a['saved_at']);

        return $saves;
    }

    /**
     * Serializes a Game entity into an array suitable for JSON encoding.
     *
     * @param Game $game
     * @return array<string, mixed>
     */
    private function serialize(Game $game): array
    {
        return [
            'version' => '1.0',
            'save_id' => $game->getSaveId(),
            'started_at' => $game->getStartedAt()->format('c'),
            'turn' => $game->getTurn(),
            'score' => $game->getScore()->getValue(),
            'in_combat' => $game->isInCombat(),
            'player' => $this->serializePlayer($game->getPlayer()),
            'dungeon' => $this->serializeDungeon($game->getDungeon()),
            'current_position' => [
                'x' => $game->getCurrentPosition()->getX(),
                'y' => $game->getCurrentPosition()->getY()
            ],
        ];
    }

    /**
     * Deserializes an array into a Game entity.
     *
     * @param array<string, mixed> $data
     * @return Game
     * @throws \DateMalformedStringException
     */
    private function deserialize(array $data): Game
    {
        // First, deserialize the player
        $player = $this->deserializePlayer($data['player']);

        // Next, deserialize the dungeon and its rooms
        $entrancePosition = new Position(
            $data['dungeon']['entrance_position']['x'],
            $data['dungeon']['entrance_position']['y']
        );

        $exitPosition = new Position(
            $data['dungeon']['exit_position']['x'],
            $data['dungeon']['exit_position']['y']
        );

        // Deserialize all rooms
        $rooms = [];
        foreach ($data['dungeon']['rooms'] as $roomData) {
            $room = $this->deserializeRoom($roomData);
            $positionKey = $room->getPosition()->toString();
            $rooms[$positionKey] = $room;
        }

        // Create the dungeon
        $dungeon = new Dungeon(
            $rooms,
            $entrancePosition,
            $exitPosition,
            $data['dungeon']['width'],
            $data['dungeon']['height'],
            $data['dungeon']['difficulty']
        );

        // Create current position
        $currentPosition = new Position(
            $data['current_position']['x'],
            $data['current_position']['y']
        );

        // Create the game with all required constructor parameters
        $game = new Game($player, $dungeon, $currentPosition);

        // Set additional properties if they exist
        if (isset($data['turn'])) {
            // If Game class has a setTurn method
            if (method_exists($game, 'setTurn')) {
                $game->setTurn($data['turn']);
            } else {
                // Alternative: use reflection to set the private property
                $reflection = new \ReflectionClass($game);
                if ($reflection->hasProperty('turn')) {
                    $property = $reflection->getProperty('turn');
                    $property->setAccessible(true);
                    $property->setValue($game, $data['turn']);
                }
            }
        }

        if (isset($data['score']) && isset($data['score']['value'])) {
            $game->getScore()->setValue($data['score']['value']);
        }

        if (isset($data['in_combat'])) {
            // If Game class has a setInCombat method
            if (method_exists($game, 'setInCombat')) {
                $game->setInCombat($data['in_combat']);
            } else {
                // Alternative approaches
                if ($data['in_combat']) {
                    $game->startCombat();
                } else {
                    $game->endCombat();
                }
            }
        }

        if (isset($data['save_id'])) {
            $game->setSaveId($data['save_id']);
        }

        if (isset($data['started_at'])) {
            // If Game class has a setStartedAt method
            if (method_exists($game, 'setStartedAt')) {
                $game->setStartedAt(new \DateTimeImmutable($data['started_at']));
            } else {
                // Alternative: use reflection to set the private property
                $reflection = new \ReflectionClass($game);
                if ($reflection->hasProperty('startedAt')) {
                    $property = $reflection->getProperty('startedAt');
                    $property->setAccessible(true);
                    $property->setValue($game, new \DateTimeImmutable($data['started_at']));
                }
            }
        }

        return $game;
    }

    /**
     * Deserializes player data into a Player entity.
     *
     * @param array<string, mixed> $data
     * @return Player
     */
    private function deserializePlayer(array $data): Player
    {
        // Create health value object
        $health = new Health(
            $data['health']['current'] ?? 0,
            $data['health']['max'] ?? 100
        );

        // Create position
        $position = new Position(
            $data['position']['x'] ?? 0,
            $data['position']['y'] ?? 0
        );

        // Get attack power from saved data or use default
        $attackPower = $data['attack_power'] ?? 20;

        // Create the player instance
        $player = new Player(
            $data['name'] ?? 'Unknown',
            $health,
            $position,
            $attackPower
        );

        // Restore inventory if present
        if (isset($data['inventory']) && is_array($data['inventory'])) {
            foreach ($data['inventory'] as $itemData) {
                // Check item type and deserialize accordingly
                if (isset($itemData['itemType']) && $itemData['itemType'] === 'item') {
                    $item = $this->deserializeItem($itemData);
                } else {
                    $item = $this->deserializeTreasure($itemData);
                }
                $player->addItem($item);
            }
        }

        // Restore equipped weapon if present
        if (isset($data['equippedWeapon']) && is_array($data['equippedWeapon'])) {
            $weaponData = $data['equippedWeapon'];

            // Check weapon type and deserialize accordingly
            if (isset($weaponData['itemType']) && $weaponData['itemType'] === 'item') {
                $weapon = $this->deserializeItem($weaponData);
            } else {
                $weapon = $this->deserializeTreasure($weaponData);
            }

            // Calculate weapon bonus based on value
            $weaponBonus = max(2, intval($weapon->getValue() / 5));

            // Equip the weapon
            $player->equipWeapon($weapon, $weaponBonus);
        }

        return $player;
    }

    /**
     * Deserializes item data into an Item entity.
     *
     * @param array<string, mixed> $data
     * @return Item
     */
    private function deserializeItem(array $data): Item
    {
        // Create the item based on your Item class structure
        // This is a placeholder - adjust based on your actual Item class
        return new Item(
            $data['name'] ?? 'Unknown Item',
            $data['description'] ?? '',
            $data['value'] ?? 0
        );
    }

    /**
     * Deserializes room data into a Room entity.
     *
     * @param array<string, mixed> $data
     * @return Room
     */
    private function deserializeRoom(array $data): Room
    {
        // Create position
        $position = new Position(
            $data['position']['x'],
            $data['position']['y']
        );

        // Process monster data if present
        $monster = null;
        if (isset($data['monster']) && is_array($data['monster'])) {
            $monster = $this->deserializeMonster($data['monster']);
        }

        // Process treasure data if present
        $treasure = null;
        if (isset($data['treasure']) && is_array($data['treasure'])) {
            $treasure = $this->deserializeTreasure($data['treasure']);
        }

        // Create the room with all constructor parameters in the correct order
        $room = new Room(
            $position,
            $data['description'] ?? 'An empty room',
            $monster, // Monster object or null
            $treasure, // Treasure object or null
            $data['is_exit'] ?? false
        );

        // Mark as visited if the room was visited
        if (isset($data['visited']) && $data['visited']) {
            $room->markAsVisited();
        }

        // Set connections
        if (isset($data['connections']) && is_array($data['connections'])) {
            foreach ($data['connections'] as $direction => $isConnected) {
                if ($isConnected) {
                    $directionEnum = Direction::from($direction);
                    $room->connectTo($directionEnum);
                }
            }
        }

        return $room;
    }

    /**
     * Deserializes monster data into a Monster entity.
     *
     * @param array<string, mixed> $data
     * @return Monster
     */
    private function deserializeMonster(array $data): Monster
    {
        // Create health
        $health = new Health(
            $data['health']['current'] ?? 0,
            $data['health']['max'] ?? 100
        );

        // Create the monster with correct constructor parameters
        // Adjust according to your Monster class constructor
        return new Monster(
            $data['name'] ?? 'Unknown Monster',
            $health,
            $data['attack_power'] ?? 10
        );
    }

    /**
     * Deserializes treasure data into a Treasure entity.
     *
     * @param array<string, mixed> $data
     * @return Treasure
     */
    private function deserializeTreasure(array $data): Treasure
    {
        // Adjust according to your Treasure class constructor
        return new Treasure(
            $data['name'] ?? 'Unknown Treasure',
            $data['value'] ?? 10,
            $data['description'] ?? 'A mysterious treasure'
        );
    }

    /**
     * Serializes the Player entity into an array.
     *
     * @param Player $player Player entity instance
     * @return array<string, mixed>
     */
    private function serializePlayer(Player $player): array
    {
        $inventory = [];

        // Process each inventory item based on its type
        foreach ($player->getInventory() as $item) {
            if ($item instanceof Treasure) {
                $inventory[] = $this->serializeTreasure($item);
            } elseif ($item instanceof Item) {
                $inventory[] = $this->serializeItem($item);
            }
        }

        // Handle equipped weapon (if any)
        $equippedWeapon = null;
        $weapon = $player->getEquippedWeapon();
        if ($weapon !== null) {
            if ($weapon instanceof Treasure) {
                $equippedWeapon = $this->serializeTreasure($weapon);
            } elseif ($weapon instanceof Item) {
                $equippedWeapon = $this->serializeItem($weapon);
            }
        }

        return [
            'name' => $player->getName(),
            'health' => [
                'current' => $player->getHealth()->getValue(),
                'max' => $player->getHealth()->getMax()
            ],
            'attack_power' => $player->getAttackPower(),
            'inventory' => $inventory,
            'equippedWeapon' => $equippedWeapon
        ];
    }

    /**
     * Serializes an Item entity into an array.
     *
     * @param Item $item Item entity instance
     * @return array<string, mixed>
     */
    private function serializeItem(Item $item): array
    {
        return [
            'itemType' => 'item', // Distinguish from treasures
            'name' => $item->getName(),
            'type' => $item->getType(),
            'value' => $item->getValue(),
            'description' => $item->getDescription()
        ];
    }

    /**
     * Serializes the Dungeon entity into an array.
     *
     * @param Dungeon $dungeon Dungeon entity instance
     * @return array<string, mixed>
     */
    private function serializeDungeon(Dungeon $dungeon): array
    {
        return [
            'width' => $dungeon->getWidth(),
            'height' => $dungeon->getHeight(),
            'difficulty' => $dungeon->getDifficulty(),
            'entrance_position' => [
                'x' => $dungeon->getEntrancePosition()->getX(),
                'y' => $dungeon->getEntrancePosition()->getY()
            ],
            'exit_position' => [
                'x' => $dungeon->getExitPosition()->getX(),
                'y' => $dungeon->getExitPosition()->getY()
            ],
            'rooms' => array_map(
                fn($room) => $this->serializeRoom($room),
                $dungeon->getAllRooms()
            )
        ];
    }

    /**
     * Serializes a Room entity into an array.
     *
     * @param Room $room Room entity instance
     * @return array<string, mixed>
     */
    private function serializeRoom(Room $room): array
    {
        $treasures = [];
        if ($room->hasTreasure() && $room->getTreasure() !== null) {
            $treasures[] = $this->serializeTreasure($room->getTreasure());
        }

        return [
            'position' => ['x' => $room->getPosition()->getX(), 'y' => $room->getPosition()->getY()],
            'description' => $room->getDescription(),
            'visited' => $room->isVisited(),
            'is_exit' => $room->isExit(),
            'monster' => $room->hasMonster() ? $this->serializeMonster($room->getMonster()) : null,
            'treasures' => $treasures,
            'connections' => $this->serializeConnections($room)
        ];
    }

    /**
     * Serializes the room connections.
     *
     * @param Room $room Room entity instance
     * @return array<string, bool>
     */
    private function serializeConnections(Room $room): array
    {
        $connections = [];
        foreach (Direction::cases() as $direction) {
            $connections[$direction->value] = $room->hasConnection($direction);
        }
        return $connections;
    }

    /**
     * Serializes a Monster entity into an array.
     *
     * @param Monster $monster Monster entity instance
     * @return array<string, mixed>
     */
    private function serializeMonster(Monster $monster): array
    {
        return [
            'name' => $monster->getName(),
            'health' => [
                'current' => $monster->getHealth()->getValue(),
                'max' => $monster->getHealth()->getMax()
            ],
            'attack_power' => $monster->getAttackPower()
        ];
    }

    /**
     * Serializes a Treasure entity into an array.
     *
     * @param Treasure $treasure Treasure entity instance
     * @return array<string, mixed>
     */
    private function serializeTreasure(Treasure $treasure): array
    {
        return [
            'name' => $treasure->getName(),
            'type' => $treasure->getType()->value,
            'value' => $treasure->getValue(),
            'description' => $treasure->getDescription()
        ];
    }
}
