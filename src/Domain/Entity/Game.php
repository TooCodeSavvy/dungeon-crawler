<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Entity;

use DungeonCrawler\Domain\ValueObject\Position;
use DungeonCrawler\Domain\ValueObject\Score;

/**
 * Represents the core game state including the player, dungeon, position, and game progression.
 *
 * Handles player movement, score tracking, combat status, and game lifecycle (victory/defeat).
 */
final class Game
{
    /**
     * @var Player The player character in the game.
     */
    private Player $player;

    /**
     * @var Dungeon The dungeon map the player is exploring.
     */
    private Dungeon $dungeon;

    /**
     * @var Position The player's current position within the dungeon.
     */
    private Position $currentPosition;

    /**
     * @var Score The player's current score.
     */
    private Score $score;

    /**
     * @var int The current turn number, incremented after each player action.
     */
    private int $turn = 1;

    /**
     * @var bool Indicates whether the player is currently engaged in combat.
     */
    private bool $inCombat = false;

    /**
     * @var \DateTimeImmutable Timestamp when the game was started.
     */
    private \DateTimeImmutable $startedAt;

    /**
     * @var string|null Optional identifier for the saved game.
     */
    private ?string $saveId = null;

    /**
     * Game constructor.
     *
     * @param Player   $player        The player instance.
     * @param Dungeon  $dungeon       The dungeon instance.
     * @param Position $startPosition The initial position of the player.
     */
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

    /**
     * Factory method to create a new game with a given player name and difficulty level.
     *
     * @param string $playerName The name of the player.
     * @param string $difficulty Difficulty setting ('easy', 'normal', 'hard').
     *
     * @return self The created Game instance.
     */
    public static function create(string $playerName, string $difficulty = 'normal'): self
    {
        $player = Player::create($playerName);

        // Determine dungeon size based on difficulty
        $dungeonSize = match($difficulty) {
            'easy' => 5,
            'hard' => 15,
            default => 10
        };

        $generator = new \DungeonCrawler\Domain\Service\DungeonGenerator();
        $dungeon = $generator->generate($dungeonSize, $difficulty);

        // Initialize game starting at the dungeon's entrance position
        return new self($player, $dungeon, $dungeon->getEntrancePosition());
    }

    /**
     * Retrieves the Room instance corresponding to the player's current position.
     *
     * @return Room The current room the player occupies.
     */
    public function getCurrentRoom(): Room
    {
        return $this->dungeon->getRoomAt($this->currentPosition);
    }

    /**
     * Moves the player to a new position within the dungeon.
     * Marks the new room as visited and increments the turn counter.
     *
     * @param Position $newPosition The position to move the player to.
     */
    public function movePlayer(Position $newPosition): void
    {
        $this->currentPosition = $newPosition;
        $this->getCurrentRoom()->markAsVisited();
        $this->incrementTurn();
    }

    /**
     * Increments the turn counter by one.
     */
    public function incrementTurn(): void
    {
        $this->turn++;
    }

    /**
     * Adds points to the player's current score.
     *
     * @param int $points Number of points to add.
     */
    public function addScore(int $points): void
    {
        $this->score = $this->score->add($points);
    }

    /**
     * Sets the combat status to active.
     */
    public function startCombat(): void
    {
        $this->inCombat = true;
    }

    /**
     * Sets the combat status to inactive.
     */
    public function endCombat(): void
    {
        $this->inCombat = false;
    }

    /**
     * Checks if the player is currently in combat.
     *
     * @return bool True if in combat, false otherwise.
     */
    public function isInCombat(): bool
    {
        return $this->inCombat;
    }

    /**
     * Determines if the game is over.
     *
     * The game is over if the player is dead or if the player has reached the exit room without monsters.
     *
     * @return bool True if the game is over, false otherwise.
     */
    public function isOver(): bool
    {
        return !$this->player->isAlive() ||
            ($this->getCurrentRoom()->isExit() && !$this->getCurrentRoom()->hasMonster());
    }

    /**
     * Determines if the player has won the game.
     *
     * Victory requires the player to be alive, in the exit room, and no monsters present.
     *
     * @return bool True if the player has won, false otherwise.
     */
    public function isVictory(): bool
    {
        return $this->player->isAlive() &&
            $this->getCurrentRoom()->isExit() &&
            !$this->getCurrentRoom()->hasMonster();
    }

    // Getters for properties

    /**
     * @return Player The player entity.
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return Dungeon The dungeon entity.
     */
    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    /**
     * @return Position The player's current position.
     */
    public function getCurrentPosition(): Position
    {
        return $this->currentPosition;
    }

    /**
     * @return Score The current score.
     */
    public function getScore(): Score
    {
        return $this->score;
    }

    /**
     * @return int The current turn number.
     */
    public function getTurn(): int
    {
        return $this->turn;
    }

    /**
     * @return \DateTimeImmutable The time when the game started.
     */
    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    /**
     * @return string|null The save identifier for this game, or null if unsaved.
     */
    public function getSaveId(): ?string
    {
        return $this->saveId;
    }

    /**
     * Sets the save identifier for this game instance.
     *
     * @param string $id Save ID string.
     */
    public function setSaveId(string $id): void
    {
        $this->saveId = $id;
    }
}
