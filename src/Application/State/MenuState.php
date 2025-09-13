<?php

declare(strict_types=1);

namespace DungeonCrawler\Application\State;

use DungeonCrawler\Application\Command\CommandInterface;
use DungeonCrawler\Application\Command\StartGameCommand;
use DungeonCrawler\Application\Command\QuitCommand;
use DungeonCrawler\Application\GameEngine;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Infrastructure\Console\ConsoleRenderer;
use DungeonCrawler\Infrastructure\Console\InputParser;

/**
 * Represents the main menu state of the game.
 */
class MenuState implements GameStateInterface
{
    private GameEngine $engine;

    public function __construct(GameEngine $engine)
    {
        $this->engine = $engine;
    }

    public function render(ConsoleRenderer $renderer, ?Game $game): void
    {
        $renderer->clear();
        $renderer->renderLine("=== Dungeon Crawler ===");
        $renderer->renderLine("1. Start New Game");
        $renderer->renderLine("2. Load Game");
        $renderer->renderLine("3. Quit");
        $renderer->renderLine("Please enter your choice:");
    }

    public function parseInput(string $input, InputParser $parser): ?CommandInterface
    {
        $input = trim($input);

        switch ($input) {
            case '1':
                // For starting a new game, you might want to get player name and difficulty.
                // For simplicity, let's assume defaults or get them later.
                return new StartGameCommand('Player', 'normal');

            case '2':
                // Transition to the load game state
                $this->engine->transitionTo(
                    $this->engine->getStateFactory()->createLoadGameState($this->engine)
                );
                return null;

            case '3':
                return new QuitCommand();

            default:
                // Invalid input returns null
                return null;
        }
    }

    public function checkTransition(?Game $game): ?GameStateInterface
    {
        // The menu state does not transition by itself, transitions happen via command results
        return null;
    }

    public function onEnter(?Game $game): void
    {
        // Nothing special on enter for now
    }

    public function onExit(?Game $game): void
    {
        // Nothing special on exit for now
    }
}
