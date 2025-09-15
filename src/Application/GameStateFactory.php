<?php
declare(strict_types=1);
namespace DungeonCrawler\Application;

use DungeonCrawler\Application\State\GameStateInterface;
use DungeonCrawler\Application\State\MenuState;
use DungeonCrawler\Application\State\PlayingState;
use DungeonCrawler\Application\State\LoadGameState;
use DungeonCrawler\Application\State\StateFactory;

/**
 * Factory class responsible for creating instances of game states.
 */
class GameStateFactory
{
    /**
     * @var StateFactory
     */
    private StateFactory $stateFactory;

    /**
     * GameStateFactory constructor.
     */
    public function __construct()
    {
        $this->stateFactory = new StateFactory();
    }

    /**
     * Creates and returns the Menu state.
     *
     * @param GameEngine $engine Reference to the main game engine.
     * @return GameStateInterface
     */
    public function createMenuState(GameEngine $engine): GameStateInterface
    {
        return new MenuState($engine);
    }

    /**
     * Creates and returns the Playing state.
     *
     * @param GameEngine $engine Reference to the main game engine.
     * @return GameStateInterface
     */
    public function createPlayingState(GameEngine $engine): GameStateInterface
    {
        return new PlayingState($engine, $this->stateFactory);
    }

    /**
     * Creates and returns the Load Game state.
     *
     * @param GameEngine $engine Reference to the main game engine.
     * @return GameStateInterface
     */
    public function createLoadGameState(GameEngine $engine): GameStateInterface
    {
        return new LoadGameState($engine);
    }
}