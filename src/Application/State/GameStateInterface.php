<?php
declare(strict_types=1);
namespace DungeonCrawler\Application\State;
use DungeonCrawler\Application\Command\CommandInterface;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Infrastructure\Console\ConsoleRenderer;
use DungeonCrawler\Infrastructure\Console\InputParser;

/**
 * Interface for game states in the State pattern.
 *
 * Different states correspond to different phases of the game (menu, playing, combat, etc.)
 * and handle rendering and input parsing differently.
 */
interface GameStateInterface
{
    /**
     * Renders the appropriate UI for this state.
     *
     * @param ConsoleRenderer $renderer The renderer to use.
     * @param Game|null $game The current game instance, if any.
     * @param string|null $actionResult Optional result from the last action to display.
     */
    public function render(ConsoleRenderer $renderer, ?Game $game, ?string $actionResult = null): void;

    /**
     * Parses user input according to the rules of this state.
     *
     * @param string $input The raw user input.
     * @param InputParser $parser The input parser to help with parsing.
     * @return CommandInterface|null A command object, or null if input couldn't be parsed.
     */
    public function parseInput(string $input, InputParser $parser): ?CommandInterface;

    /**
     * Checks if a transition to another state is needed.
     *
     * @param Game|null $game The current game, if any.
     * @return GameStateInterface|null The next state, or null if no transition is needed.
     */
    public function checkTransition(?Game $game): ?GameStateInterface;

    /**
     * Called when entering this state.
     *
     * @param Game|null $game The current game, if any.
     */
    public function onEnter(?Game $game): void;

    /**
     * Called when exiting this state.
     *
     * @param Game|null $game The current game, if any.
     */
    public function onExit(?Game $game): void;
}