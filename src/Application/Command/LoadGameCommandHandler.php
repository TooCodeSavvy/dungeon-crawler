<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Repository\GameRepositoryInterface;

/**
 * Handler for the LoadGameCommand.
 *
 * This class is responsible for handling the load game command by retrieving
 * a saved game from the repository. It encapsulates the loading logic and
 * proper error handling, returning a CommandResult with appropriate messages
 * and loaded game data.
 */
class LoadGameCommandHandler
{
    /**
     * The repository used to load saved games.
     *
     * @var GameRepositoryInterface
     */
    private GameRepositoryInterface $gameRepository;

    /**
     * Creates a new LoadGameCommandHandler with the specified repository.
     *
     * @param GameRepositoryInterface $gameRepository Repository for game persistence
     */
    public function __construct(GameRepositoryInterface $gameRepository)
    {
        $this->gameRepository = $gameRepository;
    }

    /**
     * Handles the LoadGameCommand by loading a game from the repository.
     *
     * This method attempts to load a saved game from persistent storage using
     * the save ID provided in the command. It performs validation checks and
     * wraps the loaded game in a CommandResult. If any errors occur during loading,
     * they are caught and returned as a failed CommandResult with an appropriate
     * error message.
     *
     * @param LoadGameCommand $command The command to load a game
     * @param Game|null $currentGame The current game instance (if any)
     *
     * @return CommandResult Contains success status, message, and loaded game data
     */
    public function handle(LoadGameCommand $command, ?Game $currentGame = null): CommandResult
    {
        try {
            // First check if the specified save file exists
            if (!$this->gameRepository->exists($command->getSaveId())) {
                // Return a failed result if save doesn't exist
                return new CommandResult(
                    false,
                    "Save file '{$command->getSaveId()}' not found."
                );
            }

            // Attempt to load the game from the repository
            $loadedGame = $this->gameRepository->load($command->getSaveId());

            // Return a successful result with the loaded game in the data array
            // This allows the command bus or engine to access the loaded game
            return new CommandResult(
                true,
                "Game loaded successfully from '{$command->getSaveId()}'.",
                ['game' => $loadedGame]
            );
        } catch (\Throwable $e) {
            // Log the detailed error for debugging purposes
            error_log("Error loading game: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Return a user-friendly error message without exposing implementation details
            return new CommandResult(
                false,
                "Failed to load game: " . $e->getMessage()
            );
        }
    }
}