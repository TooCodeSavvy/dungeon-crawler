<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Entity;

use DungeonCrawler\Domain\ValueObject\Position;
use DungeonCrawler\Domain\ValueObject\Score;

final class Game
{
    private Player $player;
    private Dungeon $dungeon;
    private Position $currentPosition;
    private Score $score;
    private int $turn = 1;
    private bool $inCombat = false;
    private \DateTimeImmutable $startedAt;
    private ?string $saveId = null;

    public function __construct(
        Player $player,
        Dungeon $dungeon,
        Position $startPosition
    ) {
        $this->player = $player;
        $this->dungeon = $dungeon;
        $this->currentPosition = $startPosition;
        $this->score = new Score(0);
        $this->startedAt = new \DateTimeImmutable();
    }

    public static function create(string $playerName, string $difficulty = 'normal'): self
    {
        $player = Player::create($playerName);
        $dungeonSize = match($difficulty) {
            'easy' => 5,
            'hard' => 15,
            default => 10
        };

        $generator = new \DungeonCrawler\Domain\Service\DungeonGenerator();
        $dungeon = $generator->generate($dungeonSize, $difficulty);

        return new self($player, $dungeon, $dungeon->getEntrancePosition());
    }

    public function getCurrentRoom(): Room
    {
        return $this->dungeon->getRoomAt($this->currentPosition);
    }

    public function movePlayer(Position $newPosition): void
    {
        $this->currentPosition = $newPosition;
        $this->getCurrentRoom()->markAsVisited();
        $this->incrementTurn();
    }

    public function incrementTurn(): void
    {
        $this->turn++;
    }

    public function addScore(int $points): void
    {
        $this->score = $this->score->add($points);
    }

    public function startCombat(): void
    {
        $this->inCombat = true;
    }

    public function endCombat(): void
    {
        $this->inCombat = false;
    }

    public function isInCombat(): bool
    {
        return $this->inCombat;
    }

    public function isOver(): bool
    {
        return !$this->player->isAlive() ||
            ($this->getCurrentRoom()->isExit() && !$this->getCurrentRoom()->hasMonster());
    }

    public function isVictory(): bool
    {
        return $this->player->isAlive() &&
            $this->getCurrentRoom()->isExit() &&
            !$this->getCurrentRoom()->hasMonster();
    }

    // Getters
    public function getPlayer(): Player { return $this->player; }
    public function getDungeon(): Dungeon { return $this->dungeon; }
    public function getCurrentPosition(): Position { return $this->currentPosition; }
    public function getScore(): Score { return $this->score; }
    public function getTurn(): int { return $this->turn; }
    public function getStartedAt(): \DateTimeImmutable { return $this->startedAt; }
    public function getSaveId(): ?string { return $this->saveId; }
    public function setSaveId(string $id): void { $this->saveId = $id; }
}