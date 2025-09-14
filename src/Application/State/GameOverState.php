<?php
declare(strict_types=1);
namespace DungeonCrawler\Application\State;

use DungeonCrawler\Application\Command\CommandInterface;
use DungeonCrawler\Application\Command\QuitCommand;
use DungeonCrawler\Application\Command\StartGameCommand;
use DungeonCrawler\Application\GameEngine;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Infrastructure\Console\ConsoleRenderer;
use DungeonCrawler\Infrastructure\Console\InputParser;

/**
 * Represents the game over state, either victory or defeat.
 */
class GameOverState implements GameStateInterface
{
    /**
     * @var bool Whether the player won or lost
     */
    private bool $victory;

    /**
     * @var int The final score
     */
    private int $finalScore;

    /**
     * Constructor
     *
     * @param GameEngine $engine The game engine
     * @param StateFactory $stateFactory Factory to create game states
     * @param bool $victory Whether this is a victory or defeat
     */
    public function __construct(
        private readonly GameEngine $engine,
        private readonly StateFactory $stateFactory,
        bool $victory = false
    ) {
        $this->victory = $victory;
        $this->finalScore = 0; // Will be set in onEnter
    }

    /**
     * Render the game over screen.
     */
    public function render(
        ConsoleRenderer $renderer,
        ?Game $game,
        ?string $actionResult = null,
        bool $showMap = false
    ): void {
        $renderer->clear();

        if ($this->victory) {
            $this->renderVictory($renderer, $game);
        } else {
            $this->renderDefeat($renderer, $game);
        }

        // Show available actions
        $renderer->renderAvailableActions([
            'new - Start a new game',
            'quit - Exit the game'
        ]);
    }

    /**
     * Renders the victory screen.
     */
    private function renderVictory(ConsoleRenderer $renderer, ?Game $game): void
    {
        $banner = "
        ╔═══════════════════════════════════════╗
        ║             VICTORY!                  ║
        ╚═══════════════════════════════════════╝
        ";

        $renderer->renderLine($banner);
        $renderer->renderLine("Congratulations! You have escaped the dungeon alive!");

        if ($game !== null) {
            $renderer->renderLine("");
            $renderer->renderLine("Final Score: " . $this->finalScore);
            $renderer->renderLine("Dungeon Explored: " .
                round($game->getDungeon()->getExplorationPercentage()) . "%");
            $renderer->renderLine("Turns Taken: " . $game->getTurn());
        }

        $renderer->renderLine("");
        $renderer->renderLine("Thank you for playing Dungeon Crawler!");
    }

    /**
     * Renders the defeat screen.
     */
    private function renderDefeat(ConsoleRenderer $renderer, ?Game $game): void
    {
        $banner = "
        ╔═══════════════════════════════════════╗
        ║             GAME OVER                 ║
        ╚═══════════════════════════════════════╝
        ";

        $renderer->renderLine($banner);
        $renderer->renderLine("You have been defeated in the dungeon!");

        if ($game !== null) {
            $renderer->renderLine("");
            $renderer->renderLine("Final Score: " . $this->finalScore);
            $renderer->renderLine("Dungeon Explored: " .
                round($game->getDungeon()->getExplorationPercentage()) . "%");
            $renderer->renderLine("Turns Taken: " . $game->getTurn());
        }

        $renderer->renderLine("");
        $renderer->renderLine("Better luck next time!");
    }

    /**
     * Parse player input in game over state.
     */
    public function parseInput(string $input, InputParser $parser): ?CommandInterface
    {
        $input = strtolower(trim($input));

        return match($input) {
            'new', 'start', 'play' => new StartGameCommand(),
            'quit', 'exit', 'q' => new QuitCommand(),
            default => null
        };
    }

    /**
     * No state transitions from game over.
     */
    public function checkTransition(?Game $game): ?GameStateInterface
    {
        return null;
    }

    /**
     * Store the final score when entering game over state.
     */
    public function onEnter(?Game $game): void
    {
        if ($game !== null) {
            $this->finalScore = $game->getScore()->getValue();
        }
    }

    /**
     * No cleanup needed when exiting.
     */
    public function onExit(?Game $game): void
    {
        // Nothing to clean up
    }
}