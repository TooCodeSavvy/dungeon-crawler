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
     * Creates a new GameOverState with victory or defeat.
     *
     * @param GameEngine $engine The game engine
     * @param bool $victory Whether this is a victory (true) or defeat (false)
     * @return GameOverState The game over state
     */
    public function createGameOverState(GameEngine $engine, bool $victory): GameOverState
    {
        return new GameOverState($engine, $this, $victory);
    }
}