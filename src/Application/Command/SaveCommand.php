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
     * @var bool Whether to create a new save file instead of updating existing
     */
    private bool $createNew;

    /**
     * Creates a new save command.
     *
     * @param bool $createNew Whether to create a new save file (true) or update existing (false)
     * @param GameRepositoryInterface|null $gameRepository Optional repository for saving games
     */
    public function __construct(
        bool $createNew = false,
        ?GameRepositoryInterface $gameRepository = null
    ) {
        // If no repository is provided, create a default one
        $this->gameRepository = $gameRepository ?? new JsonGameRepository();
        $this->createNew = $createNew;
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
            return CommandResult::failure("No active game to save.");
        }

        try {
            // Get current save ID
            $currentSaveId = $game->getSaveId();

            // Determine if we should create a new save or update existing
            if ($this->createNew || $currentSaveId === null) {
                // Create a new save
                $saveId = $this->gameRepository->save($game);
                $game->setSaveId($saveId);
                return CommandResult::success("Game saved successfully. Save ID: $saveId");
            } else {
                // Update existing save
                $this->gameRepository->save($game, $currentSaveId);
                return CommandResult::success("Game updated successfully. Save ID: $currentSaveId");
            }
        } catch (\Exception $e) {
            return CommandResult::failure("Failed to save game: " . $e->getMessage());
        }
    }

    /**
     * Determines if the save command can be executed.
     *
     * @param ?Game $game The current game instance.
     * @return bool Always true as games can be saved anytime.
     */
    public function canExecute(?Game $game): bool
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