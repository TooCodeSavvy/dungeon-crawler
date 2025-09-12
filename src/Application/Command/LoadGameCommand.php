<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

/**
 * Command to load a saved game by save identifier.
 *
 * This command triggers loading a previously saved game state from a repository
 * or persistence layer, identified by a save ID.
 */
class LoadGameCommand implements CommandInterface
{
    /**
     * @var string Identifier for the saved game to load.
     */
    private string $saveId;

    /**
     * Constructor.
     *
     * @param string $saveId The ID of the save file to load.
     */
    public function __construct(string $saveId)
    {
        $this->saveId = $saveId;
    }

    /**
     * Executes the load game command.
     *
     * This implementation is a stub. Actual loading logic should be
     * handled by the GameEngine or a repository service.
     *
     * @param Game $game The current game instance (may be null or active).
     * @return CommandResult Result of the load command execution.
     */
    public function execute(Game $game): CommandResult
    {
        // TODO: Implement actual loading logic here or in the handler.
        // Example:
        // $success = $game->loadSave($this->saveId);
        // if ($success) { ... }

        return new CommandResult(true, "Game loaded from save '{$this->saveId}'.");
    }

    /**
     * Determines if loading the game is currently allowed.
     *
     * You may want to check if the save ID exists or if the game state
     * allows loading at this time.
     *
     * @param Game $game The current game instance.
     * @return bool True if the load command can be executed.
     */
    public function canExecute(Game $game): bool
    {
        // Add additional checks if needed, e.g., save existence
        return true;
    }

    /**
     * Gets the internal command name.
     *
     * @return string Command identifier 'load_game'.
     */
    public function getName(): string
    {
        return 'load_game';
    }

    /**
     * Gets the save ID associated with this load command.
     *
     * @return string Save identifier.
     */
    public function getSaveId(): string
    {
        return $this->saveId;
    }
}
