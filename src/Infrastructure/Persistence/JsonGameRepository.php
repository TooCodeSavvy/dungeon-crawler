<?php
declare(strict_types=1);

namespace DungeonCrawler\Infrastructure\Persistence;

use DungeonCrawler\Domain\Entity\Game;

final class JsonGameRepository implements GameRepositoryInterface
{
    private const SAVE_DIR = __DIR__ . '/../../../data/saves/';

    public function __construct()
    {
        if (!is_dir(self::SAVE_DIR)) {
            mkdir(self::SAVE_DIR, 0777, true);
        }
    }

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

        usort($saves, fn($a, $b) => $b['saved_at'] <=> $a['saved_at']);

        return $saves;
    }

    public function delete(string $saveId): void
    {
        $filename = self::SAVE_DIR . $saveId . '.json';

        if (file_exists($filename)) {
            if (!unlink($filename)) {
                throw new \RuntimeException("Failed to delete save file: $saveId");
            }
        }
    }

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
            ]
        ];
    }

    private function deserialize(array $data): Game
    {
        // Reconstruct the game from saved data
        // This would involve recreating all entities from their serialized form
        // Implementation details would depend on your entity constructors
        throw new \RuntimeException("Deserialization not yet implemented");
    }

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

    private function serializeDungeon($dungeon): array
    {
        // Serialize dungeon structure
        return [
            'size' => $dungeon->getSize(),
            'rooms' => array_map(
                fn($room) => $this->serializeRoom($room),
                $dungeon->getRooms()
            )
        ];
    }

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
            )
        ];
    }

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