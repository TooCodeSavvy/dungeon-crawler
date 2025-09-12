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

final class ConsoleGame
{
    private GameEngine $engine;

    public function __construct()
    {
        $this->bootstrap();
    }

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

    public function start(): void
    {
        $this->engine->run();
    }
}