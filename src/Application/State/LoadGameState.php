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
     * This method displays a list of available save files with metadata such as
     * player name, turn number, and save timestamp. If no saves are found,
     * it displays a message and instructions to return to the main menu.
     *
     * @param ConsoleRenderer $renderer The renderer to use for displaying output
     * @param Game|null $game The current game instance (not used in this state)
     *
     * @return void
     */
    public function render(ConsoleRenderer $renderer, ?Game $game): void
    {
        // Clear the screen and display the state title
        $renderer->clear();
        $renderer->renderLine("=== Load Game ===");

        // Refresh the list of saves from the repository to ensure it's up-to-date
        $this->saves = $this->engine->getRepository()->listSaves();

        // Handle the case where no save files exist
        if (empty($this->saves)) {
            $renderer->renderLine("No saved games found.");
            $renderer->renderLine("");
            $renderer->renderLine("Press Enter to return to the main menu...");
            return;
        }

        // Display the heading for the save file list
        $renderer->renderLine("Available saved games:");
        $renderer->renderLine("");

        // Display each save file with its metadata in a numbered list
        $index = 1;
        foreach ($this->saves as $saveId => $save) {
            // Format the timestamp into a human-readable date/time
            $saveTime = date('Y-m-d H:i:s', $save['saved_at']);

            // Display entry with index, player name, turn number, and save time
            $renderer->renderLine("{$index}. {$save['player_name']} - Turn: {$save['turn']} - Saved: {$saveTime}");
            $index++;
        }

        // Display navigation options
        $renderer->renderLine("");
        $renderer->renderLine("0. Back to Main Menu");
        $renderer->renderLine("");

        // Prompt for user input
        $renderer->renderLine("Enter the number of the save to load:");
    }

    /**
     * Processes user input to select a save file or return to menu.
     *
     * This method handles the following user inputs:
     * - "0": Return to the main menu
     * - A valid numeric index: Create a LoadGameCommand for the selected save
     * - Any other input: Return null to indicate invalid input
     *
     * @param string $input The user's input string
     * @param InputParser $parser The input parser (not used in this simple implementation)
     *
     * @return CommandInterface|null A LoadGameCommand if a valid save was selected, null otherwise
     */
    public function parseInput(string $input, InputParser $parser): ?CommandInterface
    {
        // Remove any leading/trailing whitespace from input
        $input = trim($input);

        // Handle empty input, no saves available, or explicit menu selection
        if (empty($this->saves) || $input === '0') {
            // Transition back to the menu state
            $this->engine->transitionTo($this->engine->getStateFactory()->createMenuState($this->engine));
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
     * No special initialization is needed for the load game state.
     *
     * @param Game|null $game The current game instance
     *
     * @return void
     */
    public function onEnter(?Game $game): void
    {
        // No special initialization needed
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