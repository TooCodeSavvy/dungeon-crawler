<?php
declare(strict_types=1);
namespace DungeonCrawler\Application\State;
use DungeonCrawler\Application\Command\CommandInterface;
use DungeonCrawler\Application\Command\CommandResult;
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
    private bool $transitionPending = false;
    private ?GameStateInterface $nextState = null;

    public function __construct(GameEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Render the menu state, which displays available options for the player.
     *
     * @param ConsoleRenderer $renderer Renderer for outputting game information to the console.
     * @param Game|null $game Current game instance, not used in menu state.
     * @param string|null $actionResult Optional result from the last action to display.
     */
    public function render(ConsoleRenderer $renderer, ?Game $game, ?string $actionResult = null): void
    {
        // Skip rendering if we're transitioning to another state
        if ($this->transitionPending) {
            return;
        }

        $renderer->clear();

        // If there's an action result, display it (error messages in menu)
        if ($actionResult !== null && trim($actionResult) !== '') {
            $renderer->renderError($actionResult);
        }

        $renderer->renderLine("=== Dungeon Crawler ===");
        $renderer->renderLine("1. Start New Game");
        $renderer->renderLine("2. Load Game");
        $renderer->renderLine("3. Quit");
        $renderer->renderLine("Please enter your choice:");
    }

    /**
     * Parse user input in the menu state.
     *
     * @param string $input The user input.
     * @param InputParser $parser The input parser.
     * @return CommandInterface|null A command to execute or null if handled internally.
     */
    public function parseInput(string $input, InputParser $parser): ?CommandInterface
    {
        $input = trim($input);

        switch ($input) {
            case '1':
                // Return a command to start a new game
                return new StartGameCommand('Player', 'normal');

            case '2':
                // Mark that we want to transition and set the next state
                $this->transitionPending = true;
                $this->nextState = $this->engine->getStateFactory()->createLoadGameState($this->engine);
                // Return a placeholder command that does nothing but doesn't trigger errors
                return new class() implements CommandInterface {
                    public function execute(?Game $game): CommandResult {
                        return new CommandResult(true, "");
                    }
                    public function canExecute(?Game $game): bool {
                        return true;
                    }
                    public function getName(): string {
                        return "menu_transition";
                    }
                };

            case '3':
                // Return a quit command
                return new QuitCommand();

            default:
                // Invalid input - return null but don't show an error for menu
                return null;
        }
    }

    /**
     * Check if a transition to another state is needed.
     *
     * @param Game|null $game The current game.
     * @return GameStateInterface|null The next state or null if no transition.
     */
    public function checkTransition(?Game $game): ?GameStateInterface
    {
        if ($this->transitionPending && $this->nextState !== null) {
            $nextState = $this->nextState;
            $this->transitionPending = false;
            $this->nextState = null;
            return $nextState;
        }

        return null;
    }

    /**
     * Called when entering this state.
     *
     * @param Game|null $game The current game.
     */
    public function onEnter(?Game $game): void
    {
        // Reset transition flags when entering menu state
        $this->transitionPending = false;
        $this->nextState = null;
    }

    /**
     * Called when exiting this state.
     *
     * @param Game|null $game The current game.
     */
    public function onExit(?Game $game): void
    {
        // Nothing special needed on exit
    }
}