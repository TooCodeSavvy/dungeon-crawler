<?php

declare(strict_types=1);

namespace DungeonCrawler\Domain\Repository;

use DungeonCrawler\Domain\Entity\Game;

/**
 * Interface GameRepositoryInterface
 *
 * Defines methods for persisting and retrieving the game state.
 */
interface GameRepositoryInterface
{
    /**
     * Loads the current game state from storage by save ID.
     *
     * @param string $saveId Identifier for the saved game.
     *
     * @return Game|null Returns the loaded Game entity, or null if no saved game exists.
     */
    public function load(string $saveId): ?Game;

    /**
     * Saves the current game state to storage.
     *
     * @param Game $game The Game entity to save.
     *
     * @return string Returns the save identifier after saving.
     */
    public function save(Game $game): string;

    /**
     * Deletes the saved game state by save ID, if any.
     *
     * @param string $saveId Identifier for the saved game to delete.
     *
     * @return void
     */
    public function delete(string $saveId): void;
}
