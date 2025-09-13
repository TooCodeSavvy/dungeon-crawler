<?php
declare(strict_types=1);

namespace DungeonCrawler\Application;

use DungeonCrawler\Application\Command\CommandInterface;
use DungeonCrawler\Application\Command\CommandHandler;
use DungeonCrawler\Application\Command\LoadGameCommand;
use DungeonCrawler\Application\Command\QuitCommand;
use DungeonCrawler\Application\Command\StartGameCommand;
use DungeonCrawler\Application\State\GameStateInterface;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Repository\GameRepositoryInterface;
use DungeonCrawler\Infrastructure\Console\ConsoleRenderer;
use DungeonCrawler\Infrastructure\Console\InputParser;

/**
 * The core engine of the Dungeon Crawler game.
 *
 * Responsible for controlling the game flow, managing game states, processing input commands,
 * handling game saving/loading, and coordinating rendering.
 */
class GameEngine
{
    /**
     * @var GameStateInterface The current game state (e.g., Menu, Playing, Paused).
     */
    private GameStateInterface $currentState;

    /**
     * @var Game|null The active game instance, or null if no game is loaded/started.
     */
    private ?Game $game = null;

    /**
     * @var bool Controls the main game loop execution.
     */
    private bool $running = true;

    /**
     * Constructor.
     *
     * Initializes the GameEngine with required dependencies and sets the initial game state to the Menu.
     *
     * @param CommandHandler          $commandHandler Handles game commands.
     * @param ConsoleRenderer        $renderer       Responsible for rendering output to the console.
     * @param InputParser            $inputParser    Responsible for parsing user input.
     * @param GameRepositoryInterface $repository     Repository for saving/loading game state.
     * @param GameStateFactory       $stateFactory   Factory to create game state instances.
     */
    public function __construct(
        private readonly CommandHandler $commandHandler,
        private readonly ConsoleRenderer $renderer,
        private readonly InputParser $inputParser,
        private readonly GameRepositoryInterface $repository,
        private readonly GameStateFactory $stateFactory
    ) {
        // Start in the menu state
        $this->currentState = $this->stateFactory->createMenuState($this);
    }

    /**
     * Runs the main game loop.
     *
     * The loop continues until the game is quit.
     * It handles rendering, input parsing, command execution, and state transitions.
     *
     * @return void
     */
    public function run(): void
    {
        // Clear console and show welcome message
        $this->renderer->clear();
        $this->renderer->renderWelcome();

        while ($this->running) {
            try {
                // Render the current state with the renderer and current game instance
                $this->currentState->render($this->renderer, $this->game);

                // Read user input
                $input = $this->inputParser->getInput();

                // Parse input into a command (if any) according to the current state
                $command = $this->currentState->parseInput($input, $this->inputParser);

                // Execute the command if one was parsed
                if ($command !== null) {
                    $this->executeCommand($command);
                }

                // Check if current state wants to transition to a new state
                $this->checkStateTransitions();

            } catch (\Exception $e) {
                // Catch exceptions and display error messages without exiting
                $this->renderer->renderError($e->getMessage());
            }
        }

        // When loop ends, render goodbye message
        $this->renderer->renderGoodbye();
    }

    /**
     * Executes a command using the CommandHandler and renders any resulting messages.
     * Handles any requested state transitions after command execution.
     *
     * @param CommandInterface $command The command to execute.
     */
    private function executeCommand(CommandInterface $command): void
    {
        // Special handling for commands that don't require an active game
        // or that might create/load a game
        if ($command instanceof StartGameCommand) {
            $this->startNewGame($command->getPlayerName(), $command->getDifficulty());
            $this->renderer->renderSuccess("New game started for " . $command->getPlayerName());
            return;
        }

        if ($command instanceof LoadGameCommand) {
            $this->loadGame($command->getSaveId());
            $this->renderer->renderSuccess("Game loaded.");
            return;
        }

        if ($command instanceof QuitCommand) {

            // Execute the quit command regardless of game state
            $result = $this->commandHandler->handle($command, $this->game);

            if ($result->hasMessage()) {
                $this->renderer->renderMessage($result->getMessage());
            }

            // Set game to null to ensure we're completely resetting
            $this->game = null;

            // Always transition to menu state on quit
            $this->transitionTo($this->stateFactory->createMenuState($this));

            return;
        }
        // For all other commands that require an active game
        if ($this->game === null) {
            throw new \RuntimeException("No active game to execute this command.");
        }

        // Execute the command with the current game
        $result = $this->commandHandler->handle($command, $this->game);

        if ($result->hasMessage()) {
            $this->renderer->renderMessage($result->getMessage());
        }

        if ($result->requiresStateChange()) {
            $this->transitionTo($result->getNewState());
        }
    }

    /**
     * Checks whether the current state requests a transition to another state.
     * If so, performs the transition.
     */
    private function checkStateTransitions(): void
    {
        // Ask the current state if it wants to transition to a different state given the current game
        $nextState = $this->currentState->checkTransition($this->game);

        // If a next state is provided, perform the transition
        if ($nextState !== null) {
            $this->transitionTo($nextState);
        }
    }

    /**
     * Transitions the engine to a new game state.
     *
     * Calls the old state's onExit() and the new state's onEnter() lifecycle hooks.
     *
     * @param GameStateInterface $state The new state to transition to.
     */
    public function transitionTo(GameStateInterface $state): void
    {
        $this->currentState->onExit($this->game);
        $this->currentState = $state;
        $this->currentState->onEnter($this->game);
    }

    /**
     * Starts a new game with a given player name and difficulty.
     *
     * Creates a new Game entity and transitions to the Playing state.
     *
     * @param string $playerName The name of the player.
     * @param string $difficulty The difficulty setting (default is 'normal').
     */
    public function startNewGame(string $playerName, string $difficulty = 'normal'): void
    {
        $this->game = Game::create($playerName, $difficulty);
        $this->transitionTo($this->stateFactory->createPlayingState($this));
    }

    /**
     * Loads a saved game from repository by save ID and transitions to the Playing state.
     *
     * @param string $saveId The identifier of the saved game to load.
     */
    public function loadGame(string $saveId): void
    {
        $this->game = $this->repository->load($saveId);
        $this->transitionTo($this->stateFactory->createPlayingState($this));
    }

    /**
     * Quits the game by stopping the main loop.
     */
    public function quit(): void
    {
        $this->running = false;
    }

    /**
     * Returns the current active Game instance or null if no game is active.
     *
     * @return Game|null
     */
    public function getGame(): ?Game
    {
        return $this->game;
    }

    /**
     * Gets the game repository
     */
    public function getRepository(): GameRepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Gets the state factory
     */
    public function getStateFactory(): GameStateFactory
    {
        return $this->stateFactory;
    }
}
