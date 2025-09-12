<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\State;

use DungeonCrawler\Application\Command\CommandInterface;
use DungeonCrawler\Application\Command\MoveCommand;
use DungeonCrawler\Application\Command\AttackCommand;
use DungeonCrawler\Application\Command\TakeCommand;
use DungeonCrawler\Application\Command\SaveCommand;
use DungeonCrawler\Application\GameEngine;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Infrastructure\Console\ConsoleRenderer;
use DungeonCrawler\Infrastructure\Console\InputParser;

final class PlayingState implements GameStateInterface
{
    public function __construct(
        private readonly GameEngine $engine,
        private readonly StateFactory $stateFactory
    ) {}

    public function render(ConsoleRenderer $renderer, ?Game $game): void
    {
        if ($game === null) {
            throw new \RuntimeException('No game in playing state');
        }

        $renderer->clear();
        $renderer->renderGameStatus($game);
        $renderer->renderRoom($game->getCurrentRoom());
        $renderer->renderAvailableActions($this->getAvailableActions($game));
    }

    public function parseInput(string $input, InputParser $parser): ?CommandInterface
    {
        $parsed = $parser->parse($input);

        return match ($parsed['command']) {
            'move', 'go' => new MoveCommand($parsed['direction'] ?? ''),
            'attack', 'fight' => new AttackCommand($parsed['target'] ?? null),
            'take', 'get' => new TakeCommand($parsed['item'] ?? 'all'),
            'save' => new SaveCommand(),
            'quit' => new QuitCommand(),
            'help' => new HelpCommand(),
            'map' => new MapCommand(),
            'inventory' => new InventoryCommand(),
            default => null
        };
    }

    public function checkTransition(?Game $game): ?GameStateInterface
    {
        if ($game === null) {
            return null;
        }

        // Check for combat
        if ($game->getCurrentRoom()->hasMonster() && !$game->isInCombat()) {
            return $this->stateFactory->createCombatState($this->engine);
        }

        // Check for game over
        if (!$game->getPlayer()->isAlive()) {
            return $this->stateFactory->createGameOverState($this->engine, false);
        }

        // Check for victory
        if ($game->getCurrentRoom()->isExit() && !$game->getCurrentRoom()->hasMonster()) {
            return $this->stateFactory->createGameOverState($this->engine, true);
        }

        return null;
    }

    private function getAvailableActions(Game $game): array
    {
        $actions = ['move <direction>', 'map', 'inventory', 'save', 'quit', 'help'];

        if ($game->getCurrentRoom()->hasTreasure()) {
            $actions[] = 'take <item|all>';
        }

        return $actions;
    }

    public function onEnter(?Game $game): void
    {
        // Could play sound or show animation
    }

    public function onExit(?Game $game): void
    {
        // Cleanup if needed
    }
}