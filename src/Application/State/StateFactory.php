<?php
declare(strict_types=1);
namespace DungeonCrawler\Application\State;

use DungeonCrawler\Application\GameEngine;

/**
 * Factory class responsible for creating state objects.
 */
class StateFactory
{
    /**
     * Creates a combat state.
     *
     * @param GameEngine $engine The game engine instance.
     * @return GameStateInterface
     */
    public function createCombatState(GameEngine $engine): GameStateInterface
    {
        // This is a placeholder. You'll need to implement the CombatState class
        // return new CombatState($engine, $this);
        throw new \RuntimeException('CombatState not yet implemented');
    }

    /**
     * Creates a game over state.
     *
     * @param GameEngine $engine The game engine instance.
     * @param bool $victory Whether the game ended in victory or defeat.
     * @return GameStateInterface
     */
    public function createGameOverState(GameEngine $engine, bool $victory): GameStateInterface
    {
        // This is a placeholder. You'll need to implement the GameOverState class
        // return new GameOverState($engine, $this, $victory);
        throw new \RuntimeException('GameOverState not yet implemented');
    }
}