<?php
declare(strict_types=1);

namespace DungeonCrawler\Infrastructure\Persistence;

use DungeonCrawler\Domain\Entity\Game;

/**
 * Repository for saving and loading Game entities as JSON files.
 *
 * Saves are stored under the `data/saves/` directory relative to the project.
 */
final class JsonGameRepository implements GameRepositoryInterface
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
     * @return string The generated save ID
     * @throws \RuntimeException If saving fails
     */
    public function save(Game $game): string
    {
        $saveId = uniqid('save_', true);
        $filename = self::SAVE_DIR . $saveId . '.json';

        $data = $this->serialize($game);

        if (file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT)) === false) {
            throw new \RuntimeException("Failed to save game to file: $filename");
        }

        return $saveId;
    }

    /**
     * Loads a saved Game entity from a JSON file by save ID.
     *
     * @param string $saveId The save identifier
     * @return Game The reconstructed Game entity
     * @throws \RuntimeException If loading or parsing fails
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
     * @return array<int, array{id: string, player_name: string, turn: int, saved_at: int}>
     */
    public function listSaves(): array
    {
        $saves = [];
        $files = glob(self::SAVE_DIR . 'save_*.json');

        foreach ($files as $file) {
            $saveId = basename($file, '.json');
            $data = json_decode(file_get_contents($file), true);

            $saves[] = [
                'id' => $saveId,
                'player_name' => $data['player']['name'] ?? 'Unknown',
                'turn' => $data['turn'] ?? 0,
                'saved_at' => filemtime($file)
            ];
        }

        // Sort descending by saved_at timestamp
        usort($saves, fn($a, $b) => $b['saved_at'] <=> $a['saved_at']);

        return $saves;
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

        if (file_exists($filename)) {
            if (!unlink($filename)) {
                throw new \RuntimeException("Failed to delete save file: $saveId");
            }
        }
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
     *
     * @throws \RuntimeException Currently not implemented
     */
    private function deserialize(array $data): Game
    {
        // TODO: Implement actual deserialization of all entities here
        throw new \RuntimeException("Deserialization not yet implemented");
    }

    /**
     * Serializes the Player entity into an array.
     *
     * @param mixed $player Player entity instance
     * @return array<string, mixed>
     */
    private function serializePlayer($player): array
    {
        return [
            'name' => $player->getName(),
            'health' => [
                'current' => $player->getHealth()->getValue(),
                'max' => $player->getHealth()->getMax()
            ],
            'attack_power' => $player->getAttackPower(),
            'inventory' => array_map(
                fn($item) => $this->serializeTreasure($item),
                $player->getInventory()
            )
        ];
    }

    /**
     * Serializes the Dungeon entity into an array.
     *
     * @param mixed $dungeon Dungeon entity instance
     * @return array<string, mixed>
     */
    private function serializeDungeon($dungeon): array
    {
        return [
            'size' => $dungeon->getSize(),
            'rooms' => array_map(
                fn($room) => $this->serializeRoom($room),
                $dungeon->getRooms()
            )
        ];
    }

    /**
     * Serializes a Room entity into an array.
     *
     * @param mixed $room Room entity instance
     * @return array<string, mixed>
     */
    private function serializeRoom($room): array
    {
        return [
            'position' => ['x' => $room->getPosition()->getX(), 'y' => $room->getPosition()->getY()],
            'name' => $room->getName(),
            'description' => $room->getDescription(),
            'visited' => $room->isVisited(),
            'is_entrance' => $room->isEntrance(),
            'is_exit' => $room->isExit(),
            'monster' => $room->hasMonster() ? $this->serializeMonster($room->getMonster()) : null,
            'treasures' => array_map(
                fn($t) => $this->serializeTreasure($t),
                $room->getTreasures()
            ),
        ];
    }

    /**
     * Serializes a Monster entity into an array.
     *
     * @param mixed $monster Monster entity instance
     * @return array<string, mixed>
     */
    private function serializeMonster($monster): array
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
     * @param mixed $treasure Treasure entity instance
     * @return array<string, mixed>
     */
    private function serializeTreasure($treasure): array
    {
        return [
            'name' => $treasure->getName(),
            'type' => $treasure->getType()->value,
            'value' => $treasure->getValue(),
            'description' => $treasure->getDescription()
        ];
    }
}
