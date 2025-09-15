<?php
declare(strict_types=1);

namespace DungeonCrawler\Application;

use DungeonCrawler\Application\Command\CommandInterface;
use DungeonCrawler\Application\Command\CommandHandler;
use DungeonCrawler\Application\Command\LoadGameCommand;
use DungeonCrawler\Application\Command\MapCommand;
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
     * @var string|null Stores the result of the last action for rendering in the next loop
     */
    private ?string $lastActionResult = null;

    /**
     * @var bool Whether to show a mini-map in the next render
     */
    private bool $showMiniMap = true;

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
        $this->renderer->clear();
        $this->renderer->renderWelcome();

        // Main game loop
        while ($this->running) {
            try {
                // Render the current state with any action result from the previous iteration
                // and show the mini-map
                $this->currentState->render(
                    $this->renderer,
                    $this->game,
                    $this->lastActionResult,
                    $this->showMiniMap
                );

                // Clear the action result and mini-map flag after rendering
                $this->lastActionResult = null;
                $this->showMiniMap = true;

                // Get player input
                $this->renderer->renderPrompt($this->currentState);
                $input = fgets(STDIN);

                // Check if input reading failed
                if ($input === false) {
                    // Handle error gracefully - this could happen if stdin is closed or there's an I/O error
                    $this->lastActionResult = "Input error detected. The game will exit.";
                    $this->quit();
                    continue; // Skip to the next iteration, which will exit because running is now false
                }

                // Safely trim the input now that we know it's a string
                $input = trim($input);

                // Skip empty input
                if (empty($input)) {
                    continue;
                }

                // Parse input to command
                $command = $this->currentState->parseInput($input, $this->inputParser);

                // If no command was produced but we're not in menu/load states, show error
                if ($command === null) {
                    if (!($this->currentState instanceof MenuState) &&
                        !($this->currentState instanceof LoadGameState)) {
                        $this->lastActionResult = "Unknown command. Type 'help' for available commands.";
                    }
                    // Continue the loop without executing a command
                    continue;
                }

                // Execute the command if it's not null
                $this->executeCommand($command);

                // Check for state transitions
                $this->checkStateTransitions();

            } catch (\Exception $e) {
                // Handle any exceptions and store the error message
                $this->lastActionResult = "Error: " . $e->getMessage();
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
        // Special handling for menu transition command
        if ($command->getName() === "menu_transition") {
            // Do nothing - the transition will be handled in checkStateTransitions()
            return;
        }

        // Special handling for MapCommand
        if ($command instanceof MapCommand) {
            $this->handleMapCommand($command);
            return;
        }

        // Special handling for commands that don't require an active game
        // or that might create/load a game
        if ($command instanceof StartGameCommand) {
            $this->startNewGame($command->getPlayerName(), $command->getDifficulty());
            return;
        }

        if ($command instanceof LoadGameCommand) {
            try {
                $this->loadGame($command->getSaveId());
            } catch (\Exception $e) {
                $this->renderer->renderError("Failed to load game: " . $e->getMessage());
            }
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
            // Signal to exit the main loop
            $this->quit();
            return;
        }

        // For all other commands that require an active game
        if ($this->game === null) {
            $this->renderer->renderError("No active game to execute this command.");
            return;
        }

        // Execute the command with the current game
        $result = $this->commandHandler->handle($command, $this->game);

        // Store the message for the next rendering cycle
        if ($result->hasMessage()) {
            $this->lastActionResult = $result->getMessage();
        }

        // Check if we should show a mini-map (for movement commands)
        if ($result->hasData('showMiniMap') && $result->get('showMiniMap') === true) {
            $this->showMiniMap = true;
        } else {
            $this->showMiniMap = false;
        }

        // Handle state transitions if needed
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

    /**
     * Handles the map command with special rendering.
     *
     * @param MapCommand $command The map command to execute.
     */
    private function handleMapCommand(MapCommand $command): void
    {
        if ($this->game === null) {
            $this->lastActionResult = "No active game to display map for.";
            return;
        }

        $result = $this->commandHandler->handle($command, $this->game);

        if ($result->isSuccess()) {
            // Use the dedicated map renderer with the game object
            $this->renderer->renderFullscreenMap($this->game);

            // Wait for user to press Enter to continue
            fgets(STDIN);

            // Don't set lastActionResult for map command
        } else {
            // Only set lastActionResult for error cases
            $this->lastActionResult = $result->getMessage();
        }
    }
}
