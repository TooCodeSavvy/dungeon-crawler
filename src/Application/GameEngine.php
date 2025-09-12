<?php
declare(strict_types=1);

namespace DungeonCrawler\Application;

use DungeonCrawler\Application\Command\CommandInterface;
use DungeonCrawler\Application\Command\CommandHandler;
use DungeonCrawler\Application\State\GameStateInterface;
use DungeonCrawler\Application\State\MenuState;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Repository\GameRepositoryInterface;
use DungeonCrawler\Infrastructure\Console\ConsoleRenderer;
use DungeonCrawler\Infrastructure\Console\InputParser;

final class GameEngine
{
    private GameStateInterface $currentState;
    private ?Game $game = null;
    private bool $running = true;

    public function __construct(
        private readonly CommandHandler $commandHandler,
        private readonly ConsoleRenderer $renderer,
        private readonly InputParser $inputParser,
        private readonly GameRepositoryInterface $repository,
        private readonly GameStateFactory $stateFactory
    ) {
        $this->currentState = $this->stateFactory->createMenuState($this);
    }

    public function run(): void
    {
        $this->renderer->clear();
        $this->renderer->renderWelcome();

        while ($this->running) {
            try {
                // Render current state
                $this->currentState->render($this->renderer, $this->game);

                // Get and parse input
                $input = $this->inputParser->getInput();
                $command = $this->currentState->parseInput($input, $this->inputParser);

                if ($command !== null) {
                    $this->executeCommand($command);
                }

                // Check for state transitions
                $this->checkStateTransitions();

            } catch (\Exception $e) {
                $this->renderer->renderError($e->getMessage());
            }
        }

        $this->renderer->renderGoodbye();
    }

    private function executeCommand(CommandInterface $command): void
    {
        $result = $this->commandHandler->handle($command, $this->game);

        if ($result->hasMessage()) {
            $this->renderer->renderMessage($result->getMessage());
        }

        if ($result->requiresStateChange()) {
            $this->transitionTo($result->getNewState());
        }
    }

    private function checkStateTransitions(): void
    {
        $nextState = $this->currentState->checkTransition($this->game);

        if ($nextState !== null) {
            $this->transitionTo($nextState);
        }
    }

    public function transitionTo(GameStateInterface $state): void
    {
        $this->currentState->onExit($this->game);
        $this->currentState = $state;
        $this->currentState->onEnter($this->game);
    }

    public function startNewGame(string $playerName, string $difficulty = 'normal'): void
    {
        $this->game = Game::create($playerName, $difficulty);
        $this->transitionTo($this->stateFactory->createPlayingState($this));
    }

    public function loadGame(string $saveId): void
    {
        $this->game = $this->repository->load($saveId);
        $this->transitionTo($this->stateFactory->createPlayingState($this));
    }

    public function saveGame(): void
    {
        if ($this->game === null) {
            throw new \RuntimeException('No game to save');
        }

        $this->repository->save($this->game);
        $this->renderer->renderSuccess('Game saved successfully!');
    }

    public function quit(): void
    {
        $this->running = false;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }
}