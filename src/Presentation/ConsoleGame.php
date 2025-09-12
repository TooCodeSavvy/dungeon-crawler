<?php

declare(strict_types=1);

namespace DungeonCrawler\Presentation;

use DungeonCrawler\Application\GameEngine;
use DungeonCrawler\Application\Command\CommandHandler;
use DungeonCrawler\Domain\Service\CombatService;
use DungeonCrawler\Domain\Service\MovementService;
use DungeonCrawler\Infrastructure\Console\ConsoleRenderer;
use DungeonCrawler\Infrastructure\Console\InputParser;
use DungeonCrawler\Infrastructure\Persistence\JsonGameRepository;

/**
 * ConsoleGame handles wiring and running the console-based DungeonCrawler game.
 */
class ConsoleGame
{
    private GameEngine $engine;

    /**
     * ConsoleGame constructor.
     */
    public function __construct()
    {
        $this->bootstrap();
    }

    /**
     * Bootstraps services, infrastructure, command handler, and game engine.
     */
    private function bootstrap(): void
    {
        // Initialize services
        $movementService = new MovementService();
        $combatService = new CombatService();

        // Initialize infrastructure
        $renderer = new ConsoleRenderer();
        $inputParser = new InputParser();
        $repository = new JsonGameRepository();

        // Initialize command handler
        $commandHandler = new CommandHandler($movementService, $combatService);

        // Initialize game engine
        $this->engine = new GameEngine(
            $commandHandler,
            $renderer,
            $inputParser,
            $repository
        );
    }

    /**
     * Starts the game loop.
     */
    public function start(): void
    {
        $this->engine->run();
    }
}