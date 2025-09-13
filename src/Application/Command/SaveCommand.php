<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Repository\GameRepositoryInterface;
use DungeonCrawler\Infrastructure\Persistence\JsonGameRepository;

/**
 * Command to save the current game state for later loading.
 */
class SaveCommand implements CommandInterface
{
    /**
     * @var GameRepositoryInterface Repository for saving game state
     */
    private GameRepositoryInterface $gameRepository;

    /**
     * @param GameRepositoryInterface|null $gameRepository Optional repository for saving games
     */
    public function __construct(?GameRepositoryInterface $gameRepository = null)
    {
        // If no repository is provided, create a default one
        $this->gameRepository = $gameRepository ?? new JsonGameRepository();
    }

    /**
     * Executes the save command, persisting the current game state.
     *
     * @param ?Game $game The current game instance.
     * @return CommandResult The result of executing the command.
     */
    public function execute(?Game $game): CommandResult
    {
        if ($game === null) {
            return new CommandResult(false, "No active game to save.");
        }

        try {
            // Save the game using your repository
            $saveId = $this->gameRepository->save($game);

            // Update the game with the save ID
            $game->setSaveId($saveId);

            return new CommandResult(true, "Game saved successfully. Save ID: $saveId");
        } catch (\Exception $e) {
            return new CommandResult(false, "Failed to save game: " . $e->getMessage());
        }
    }

    /**
     * Determines if the save command can be executed.
     *
     * @param Game $game The current game instance.
     * @return bool Always true as games can be saved anytime.
     */
    public function canExecute(Game $game): bool
    {
        return true;
    }

    /**
     * Gets the name of the command.
     *
     * @return string The command name.
     */
    public function getName(): string
    {
        return 'save';
    }
}