<?php
declare(strict_types=1);
namespace DungeonCrawler\Application\State;

use DungeonCrawler\Application\Command\CommandInterface;
use DungeonCrawler\Application\Command\LoadGameCommand;
use DungeonCrawler\Application\GameEngine;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Infrastructure\Console\ConsoleRenderer;
use DungeonCrawler\Infrastructure\Console\InputParser;

/**
 * State for displaying and selecting saved games to load.
 *
 * This state provides a user interface for browsing and selecting existing saved games.
 * It retrieves a list of available saves from the repository, displays them with relevant
 * metadata (player name, turn number, save timestamp), and processes user selection.
 * The state can transition back to the menu or produce a LoadGameCommand based on user input.
 */
class LoadGameState implements GameStateInterface
{
    /**
     * Reference to the game engine.
     *
     * @var GameEngine
     */
    private GameEngine $engine;

    /**
     * Whether the state is currently in save deletion mode.
     *
     * @var bool
     */
    private bool $deleteMode = false;

    /**
     * Cache of available save games and their metadata.
     *
     * Format: [saveId => ['player_name' => string, 'turn' => int, 'saved_at' => int]]
     *
     * @var array<string, array<string, mixed>>
     */
    private array $saves = [];

    /**
     * Creates a new LoadGameState.
     *
     * @param GameEngine $engine Reference to the game engine for state transitions and repository access
     */
    public function __construct(GameEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Renders the load game UI showing available save files.
     *
     * @param ConsoleRenderer $renderer The renderer to use
     * @param Game|null $game The current game (not used in this state)
     * @param string|null $actionResult Optional result from previous action
     * @return void
     */
    public function render(ConsoleRenderer $renderer, ?Game $game, ?string $actionResult = null): void
    {
        // Clear the screen and display the state title
        $renderer->clear();

        // Display any action result if provided
        if ($actionResult !== null && !empty(trim($actionResult))) {
            if (str_contains(strtolower($actionResult), 'error')) {
                $renderer->renderError($actionResult);
            } else {
                $renderer->renderMessage($actionResult);
            }
        }

        $renderer->renderLine("=== Load Game ===");

        // Refresh the list of saves from the repository
        $this->saves = $this->engine->getRepository()->listSaves();

        // Handle the case where no save files exist
        if (empty($this->saves)) {
            $renderer->renderLine("No saved games found.");
            $renderer->renderLine("");
            $renderer->renderLine("Press Enter to return to the main menu...");
            return;
        }

        // Display appropriate heading based on mode
        if ($this->deleteMode) {
            $renderer->renderLine("Select a save to DELETE:");
            $renderer->renderLine("WARNING: This action cannot be undone!");
        } else {
            $renderer->renderLine("Available saved games:");
        }

        $renderer->renderLine("");

        // Display each save file with its metadata in a numbered list
        $index = 1;
        foreach ($this->saves as $saveId => $save) {
            // Format the timestamp into a human-readable relative time
            $relativeTime = $this->getRelativeTimeString($save['saved_at']);
            // Display entry with index, player name, turn number, and relative time
            $renderer->renderLine("{$index}. {$save['player_name']} - Turn: {$save['turn']} - Saved: {$relativeTime}");
            $index++;
        }

        // Display navigation options
        $renderer->renderLine("");

        if ($this->deleteMode) {
            $renderer->renderLine("0. Cancel deletion");
        } else {
            $renderer->renderLine("d. Delete a save");
            $renderer->renderLine("0. Back to Main Menu");
        }

        $renderer->renderLine("");

        // Prompt for user input based on current mode
        if ($this->deleteMode) {
            $renderer->renderLine("Enter the number of the save to DELETE (or 0 to cancel):");
        } else {
            $renderer->renderLine("Enter the number of the save to load (or 'd' to delete):");
        }
    }

    /**
     * Converts a timestamp to a human-readable relative time string.
     *
     * @param int $timestamp The timestamp to convert
     * @return string A relative time string (e.g., "2 minutes ago", "1 day ago")
     */
    private function getRelativeTimeString(int $timestamp): string
    {
        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 60) {
            return "just now";
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . " day" . ($days > 1 ? "s" : "") . " ago";
        } else {
            return date('Y-m-d H:i:s', $timestamp);
        }
    }

    /**
     * Processes user input to select a save file, delete a save, or return to menu.
     *
     * @param string $input The user's input string
     * @param InputParser $parser The input parser
     *
     * @return CommandInterface|null A LoadGameCommand if a valid save was selected, null otherwise
     */
    public function parseInput(string $input, InputParser $parser): ?CommandInterface
    {
        // Remove any leading/trailing whitespace from input
        $input = trim(strtolower($input));

        // Handle empty input, no saves available, or explicit menu selection
        if (empty($this->saves)) {
            // Transition back to the menu state
            $this->engine->transitionTo($this->engine->getStateFactory()->createMenuState($this->engine));
            return null;
        }

        // Handle inputs that work in either mode
        if ($input === '0') {
            if ($this->deleteMode) {
                // Exit delete mode and stay on the load screen
                $this->deleteMode = false;
                return null;
            } else {
                // Return to main menu
                $this->engine->transitionTo($this->engine->getStateFactory()->createMenuState($this->engine));
                return null;
            }
        }

        // Enter delete mode
        if (!$this->deleteMode && ($input === 'd' || $input === 'delete')) {
            $this->deleteMode = true;
            return null;
        }

        // If in delete mode, process deletion
        if ($this->deleteMode) {
            // Convert input to number
            $selection = intval($input);

            if ($selection > 0 && $selection <= count($this->saves)) {
                $saveIndex = $selection - 1;
                $saveIds = array_keys($this->saves);

                if (isset($saveIds[$saveIndex])) {
                    $saveId = $saveIds[$saveIndex];
                    // Delete the save
                    $this->engine->getRepository()->delete($saveId);
                    // Exit delete mode
                    $this->deleteMode = false;
                    return null;
                }
            }

            // Invalid input in delete mode
            $this->deleteMode = false;
            return null;
        }

        // Attempt to convert input to an integer for save selection
        $selection = intval($input);

        // Validate the selection is within the range of available saves
        if ($selection > 0 && $selection <= count($this->saves)) {
            // Convert the 1-based user selection to a 0-based array index
            $saveIndex = $selection - 1;

            // Get the array of save IDs
            $saveIds = array_keys($this->saves);

            // Check if the selected index exists in our save ID array
            if (isset($saveIds[$saveIndex])) {
                // Get the save ID string for the selected index
                $saveId = $saveIds[$saveIndex];

                // Create and return a command to load the selected save
                return new LoadGameCommand($saveId);
            }
        }

        // Return null for invalid input to indicate no valid command could be created
        return null;
    }

    /**
     * Checks if this state should automatically transition to another state.
     *
     * The load game state has no automatic transitions - it only changes state
     * when the user explicitly selects an option.
     *
     * @param Game|null $game The current game instance
     *
     * @return GameStateInterface|null Always returns null as this state doesn't auto-transition
     */
    public function checkTransition(?Game $game): ?GameStateInterface
    {
        // No automatic transitions from this state
        return null;
    }

    /**
     * Called when entering this state.
     *
     * Reset delete mode to ensure we always start in load mode.
     *
     * @param Game|null $game The current game instance
     *
     * @return void
     */
    public function onEnter(?Game $game): void
    {
        // Reset delete mode when entering this state
        $this->deleteMode = false;
    }

    /**
     * Called when exiting this state.
     *
     * No cleanup is needed when leaving the load game state.
     *
     * @param Game|null $game The current game instance
     *
     * @return void
     */
    public function onExit(?Game $game): void
    {
        // No cleanup needed
    }
}