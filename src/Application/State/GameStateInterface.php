<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\State;

use DungeonCrawler\Application\Command\CommandInterface;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Infrastructure\Console\ConsoleRenderer;
use DungeonCrawler\Infrastructure\Console\InputParser;

/**
 * Interface for game states.
 *
 * Defines the contract that all game states must implement to handle rendering,
 * input parsing, state transitions, and lifecycle hooks.
 */
interface GameStateInterface
{
    /**
     * Render the current state to the console.
     *
     * @param ConsoleRenderer $renderer Renderer used to output to console.
     * @param Game|null $game Current game instance, may be null depending on state.
     */
    public function render(ConsoleRenderer $renderer, ?Game $game): void;

    /**
     * Parse player input and convert it into a command.
     *
     * @param string $input Raw input string from player.
     * @param InputParser $parser Helper to parse input into structured data.
     * @return CommandInterface|null Parsed command or null if input is invalid.
     */
    public function parseInput(string $input, InputParser $parser): ?CommandInterface;

    /**
     * Check if the game should transition to another state.
     *
     * @param Game|null $game Current game instance.
     * @return GameStateInterface|null Next state if transition is required, null otherwise.
     */
    public function checkTransition(?Game $game): ?GameStateInterface;

    /**
     * Called when entering this state.
     *
     * @param Game|null $game Current game instance.
     */
    public function onEnter(?Game $game): void;

    /**
     * Called when exiting this state.
     *
     * @param Game|null $game Current game instance.
     */
    public function onExit(?Game $game): void;
}
