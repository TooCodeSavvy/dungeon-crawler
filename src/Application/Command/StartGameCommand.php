<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

/**
 * Command to start a new game session.
 *
 * This command holds the information necessary to start a new game,
 * such as the player's name and the selected difficulty level.
 *
 * When executed, it signals the GameEngine to create a new Game instance.
 * It is allowed to execute only when no game is currently active.
 */
class StartGameCommand implements CommandInterface
{
    /**
     * @var string The name of the player starting the game.
     */
    private string $playerName;

    /**
     * @var string The difficulty level for the new game (e.g., 'easy', 'normal', 'hard').
     */
    private string $difficulty;

    /**
     * Constructor.
     *
     * @param string $playerName Name of the player starting the game.
     * @param string $difficulty Difficulty level selected.
     */
    public function __construct(string $playerName, string $difficulty)
    {
        $this->playerName = $playerName;
        $this->difficulty = $difficulty;
    }

    /**
     * Executes the start game command.
     *
     * If a game instance is already active, execution is denied.
     * Otherwise, signals success and expects the GameEngine to create the new game.
     *
     * @param Game|null $game The current game instance or null if none active.
     * @return CommandResult Result of command execution.
     */
    public function execute(?Game $game): CommandResult
    {
        if ($game !== null) {
            // Cannot start a new game if one is already active.
            return new CommandResult(false, "Game already started.");
        }

        // Command accepted; GameEngine should create the new Game instance.
        return new CommandResult(true, "Start new game command received.", false);
    }

    /**
     * Determines if this command can currently be executed.
     *
     * Typically, starting a new game is always allowed when no game is active.
     * This method could be extended to add more sophisticated checks.
     *
     * @param ?Game $game The current game instance.
     * @return bool True if command can be executed, false otherwise.
     */
    public function canExecute(?Game $game): bool
    {
        return true;
    }

    /**
     * Returns the internal name of the command.
     *
     * @return string Command identifier.
     */
    public function getName(): string
    {
        return 'start_game';
    }

    /**
     * Gets the player name for the new game.
     *
     * @return string Player's name.
     */
    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    /**
     * Gets the difficulty level for the new game.
     *
     * @return string Difficulty setting.
     */
    public function getDifficulty(): string
    {
        return $this->difficulty;
    }
}
