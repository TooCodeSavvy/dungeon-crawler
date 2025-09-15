<?php
declare(strict_types=1);
namespace DungeonCrawler\Infrastructure\Console;
use DungeonCrawler\Domain\Entity\Dungeon;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Entity\Room;
use DungeonCrawler\Domain\ValueObject\Direction;
use DungeonCrawler\Domain\ValueObject\Position;

/**
 * Responsible for rendering game output to the console.
 *
 * Provides methods to display game status, rooms, messages, and UI elements
 * using ANSI escape codes for color and formatting to enhance the console experience.
 */
class ConsoleRenderer
{
    // ANSI escape codes for console text colors and styles
    private const COLOR_RESET = "\033[0m";
    private const COLOR_RED = "\033[31m";
    private const COLOR_GREEN = "\033[32m";
    private const COLOR_YELLOW = "\033[33m";
    private const COLOR_BLUE = "\033[34m";
    private const COLOR_MAGENTA = "\033[35m";
    private const COLOR_CYAN = "\033[36m";
    private const COLOR_WHITE = "\033[37m";
    private const COLOR_BOLD = "\033[1m";

    /**
     * Clears the console screen and moves the cursor to the top-left corner.
     */
    public function clear(): void
    {
        echo "\033[2J\033[H";
    }

    /**
     * Renders a visually distinct section for action results.
     *
     * @param string $result The action result message to display
     */
    public function renderActionResult(string $result): void
    {
        echo self::COLOR_BOLD . self::COLOR_YELLOW . "╔═ ACTION RESULT " . str_repeat("═", 24) . "╗" . self::COLOR_RESET . "\n";
        echo "║ " . wordwrap($result, 38, "\n║ ") . "\n";
        echo self::COLOR_BOLD . self::COLOR_YELLOW . "╚" . str_repeat("═", 39) . "╝" . self::COLOR_RESET . "\n\n";
    }

    /**
     * Renders the welcome banner for the game with styling.
     */
    public function renderWelcome(): void
    {
        $this->renderBorder();
        echo self::COLOR_BOLD . self::COLOR_CYAN;
        echo "     ╔══════════════════════════════════════╗\n";
        echo "     ║        DUNGEON CRAWLER v1.0          ║\n";
        echo "     ║      A Text Adventure Game           ║\n";
        echo "     ╚══════════════════════════════════════╝\n";
        echo self::COLOR_RESET . "\n";
    }

    /**
     * Renders a goodbye message when the game is exited.
     *
     * @return void
     */
    public function renderGoodbye(): void
    {
        $this->clear();
        echo "═════════════════════════════════════════════\n";
        echo "     Thank you for playing Dungeon Crawler!   \n";
        echo "     ╔══════════════════════════════════════╗\n";
        echo "     ║        DUNGEON CRAWLER v1.0          ║\n";
        echo "     ║          See you soon!               ║\n";
        echo "     ╚══════════════════════════════════════╝\n";
        echo "═════════════════════════════════════════════\n";
    }

    /**
     * Displays the current game status, including player name, health, turn number, and current room.
     *
     * @param Game $game The current game state
     */
    public function renderGameStatus(Game $game): void
    {
        $player = $game->getPlayer();
        $health = $player->getHealth();
        $healthBar = $this->createHealthBar($health->getValue(), $health->getMax());
        echo self::COLOR_BOLD . "═══════════════════════════════════════════\n" . self::COLOR_RESET;
        echo sprintf(
            "%s%s%s | HP: %s | Turn: %d | Room: %s\n",
            self::COLOR_BOLD,
            $player->getName(),
            self::COLOR_RESET,
            $healthBar,
            $game->getTurn(),
            $game->getCurrentRoom()->getName()
        );
        echo "═══════════════════════════════════════════\n\n";
    }

    /**
     * Renders the current room's name, description, and any monsters or treasures present.
     *
     * @param Room $room The room to render
     */
    public function renderRoom(Room $room): void
    {
        // Display room name in cyan with location icon
        echo self::COLOR_CYAN . "📍 " . $room->getName() . self::COLOR_RESET . "\n";
        echo $room->getDescription() . "\n\n";

        // If a monster is present, display its name and health bar in red
        if ($room->hasMonster()) {
            $monster = $room->getMonster();
            echo self::COLOR_RED . "⚔️  A " . $monster->getName() . " blocks your path!" . self::COLOR_RESET . "\n";
            echo $this->createHealthBar($monster->getHealth()->getValue(), $monster->getHealth()->getMax()) . "\n";
        }

        // If treasures are present, list them with appropriate styling
        if ($room->hasTreasure()) {
            $treasures = $room->getTreasures();

            // Adjust the message based on the number of treasures
            if (count($treasures) === 1) {
                echo self::COLOR_YELLOW . "✨ You see a treasure here:" . self::COLOR_RESET . "\n";
            } else {
                echo self::COLOR_YELLOW . "✨ You see " . count($treasures) . " treasures here:" . self::COLOR_RESET . "\n";
            }

            // Display each treasure with its own icon, color, and details
            foreach ($treasures as $treasure) {
                $type = $treasure->getType();
                $icon = $type->getIcon();
                $color = $type->getColor();

                // Display treasure with type-specific color
                echo "   " . $icon . " " . $color . $treasure->getName() . self::COLOR_RESET;

                // Display info
                $displayInfo = $treasure->getDisplayInfo();
                echo " (" . $displayInfo['rarity'] . ")\n";

                // Show description with a slight indent
                echo "      " . $displayInfo['description'] . "\n";
            }
            echo "\n";
        }

        // Indicate if this room is the exit in green with a door icon
        if ($room->isExit()) {
            echo self::COLOR_GREEN . "🚪 This is the exit!" . self::COLOR_RESET . "\n";
        }
    }

    /**
     * Creates a colored health bar string representing current vs max health.
     *
     * The bar changes color based on the percentage of health remaining:
     * green (>60%), yellow (>30%), red (<=30%).
     *
     * @param int $current Current health value
     * @param int $max Maximum health value
     * @return string Colored health bar with numeric values
     */
    private function createHealthBar(int $current, int $max): string
    {
        $percentage = ($current / $max) * 100;
        $barLength = 20;
        $filled = (int) (($percentage / 100) * $barLength);
        $color = match (true) {
            $percentage > 60 => self::COLOR_GREEN,
            $percentage > 30 => self::COLOR_YELLOW,
            default => self::COLOR_RED
        };
        $bar = $color . str_repeat('█', $filled) .
            self::COLOR_WHITE . str_repeat('░', $barLength - $filled) .
            self::COLOR_RESET;
        return sprintf("%s %d/%d", $bar, $current, $max);
    }

    /**
     * Renders a list of available actions for the player to choose from.
     *
     * @param string[] $actions Array of action descriptions
     */
    public function renderAvailableActions(array $actions): void
    {
        echo self::COLOR_BOLD . "Available Actions:" . self::COLOR_RESET . "\n";
        foreach ($actions as $action) {
            echo "  • " . $action . "\n";
        }
        echo "\n";
    }

    /**
     * Renders an error message in red with an error icon.
     *
     * @param string $message Error message to display
     */
    public function renderError(string $message): void
    {
        echo self::COLOR_RED . "❌ Error: " . $message . self::COLOR_RESET . "\n\n";
    }

    /**
     * Renders a success message in green with a checkmark icon.
     *
     * @param string $message Success message to display
     */
    public function renderSuccess(string $message): void
    {
        echo self::COLOR_GREEN . "✓ " . $message . self::COLOR_RESET . "\n\n";
    }

    /**
     * Renders a generic message without additional styling.
     *
     * @param string $message Message to display
     */
    public function renderMessage(string $message): void
    {
        echo $message . "\n\n";
    }

    /**
     * Renders the prompt symbol for user input.
     */
    public function renderPrompt(): void
    {
        echo self::COLOR_BOLD . "> " . self::COLOR_RESET;
    }

    /**
     * Renders a horizontal border line for UI separation.
     */
    private function renderBorder(): void
    {
        echo str_repeat("═", 45) . "\n";
    }

    /**
     * Renders a single line of text followed by a newline.
     *
     * @param string $text The text to render.
     */
    public function renderLine(string $text): void
    {
        echo $text . PHP_EOL;
    }

    /**
     * Renders a dungeon map with the specified display options.
     *
     * @param Game $game The current game instance
     * @param bool $isFullScreen Whether to render in fullscreen mode with continue prompt
     */
    private function renderMapInternal(Game $game, bool $isFullScreen = false): void
    {
        // Generate the map content
        $mapContent = $this->generateMapContent($game);

        // Split the content into parts
        $lines = explode("\n", $mapContent);
        $header = array_shift($lines); // First line is the title
        $legend = array_shift($lines); // Second line is the legend
        array_shift($lines); // Remove the blank line after the legend

        // The rest is the actual map
        $mapDisplay = implode("\n", $lines);

        // Display header with styling
        echo self::COLOR_BOLD . self::COLOR_CYAN;
        echo "╔═══════════════ DUNGEON MAP ════════════════╗\n";
        echo "║                                            ║\n";
        echo "║  " . $header . str_repeat(" ", max(0, 38 - strlen($header))) . "║\n";
        echo "╚═══════════════════════════════════════════╝\n";
        echo self::COLOR_RESET;

        // Add extra spacing for fullscreen mode
        if ($isFullScreen) {
            echo "\n";
        }

        // Display legend
        echo $legend . "\n";

        // Add extra spacing for fullscreen mode
        if ($isFullScreen) {
            echo "\n";
        }

        // Display the actual map
        echo $mapDisplay;

        // Show prompt to continue if in fullscreen mode
        if ($isFullScreen) {
            echo "\n\n" . self::COLOR_YELLOW . "Press Enter to continue..." . self::COLOR_RESET;
        } else {
            echo "\n";
        }
    }

    /**
     * Renders the dungeon map in fullscreen mode with a "Press Enter to continue" prompt.
     * This is used for the explicit 'map' command.
     *
     * @param Game $game The current game instance
     */
    public function renderFullscreenMap(Game $game): void
    {
        $this->renderMapInternal($game, true);
    }

    /**
     * Renders an automatically generated map during normal gameplay.
     * This is displayed after movement without requiring user input to continue.
     *
     * @param Game $game The current game instance
     */
    public function renderAutoMap(Game $game): void
    {
        $this->renderMapInternal($game, false);
    }

    /**
     * Generates the map content as a string.
     * This handles the actual map generation logic with colorized elements.
     *
     * @param Game $game The current game instance.
     * @return string The formatted map content with header and legend.
     */
    private function generateMapContent(Game $game): string
    {
        $dungeon = $game->getDungeon();
        $currentPosition = $game->getCurrentPosition();
        $width = $dungeon->getWidth();
        $height = $dungeon->getHeight();

        // Start with header and legend
        $output = "Current Dungeon Map\n";
        $output .= "Legend: [P] Player | [X] Exit | [·] Unexplored | [O] Explored | [M] Monster | [T] Treasure\n\n";

        // Define ANSI color codes for map elements
        $colorPlayer = "\033[1;32m"; // Bold green
        $colorExit = "\033[1;36m";   // Bold cyan
        $colorMonster = "\033[1;31m"; // Bold red
        $colorTreasure = "\033[1;33m"; // Bold yellow
        $colorVisited = "\033[1;37m"; // Bold white
        $colorUnexplored = "\033[0;37m"; // Gray
        $colorAdjacent = "\033[0;36m"; // Cyan
        $colorReset = "\033[0m";      // Reset

        // Generate the map grid
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $position = new Position($x, $y);
                $room = $dungeon->getRoomAt($position);

                // If no room at this position (wall)
                if ($room === null) {
                    $output .= '   ';
                    continue;
                }

                // Check if the room is visible - either visited or adjacent to a visited room
                $isVisible = $room->isVisited() || $this->isAdjacentToVisited($dungeon, $position);
                if (!$isVisible) {
                    $output .= $colorUnexplored . ' · ' . $colorReset;
                    continue;
                }

                // Determine what to display for this room with colors
                if ($position->equals($currentPosition)) {
                    $output .= $colorPlayer . '[P]' . $colorReset; // Player position
                } elseif ($room->isExit()) {
                    $output .= $colorExit . '[X]' . $colorReset; // Exit
                } elseif ($room->hasMonster() && $room->isVisited()) {
                    $output .= $colorMonster . '[M]' . $colorReset; // Monster
                } elseif ($room->hasTreasure() && $room->isVisited()) {
                    $output .= $colorTreasure . '[T]' . $colorReset; // Treasure
                } elseif ($room->isVisited()) {
                    $output .= $colorVisited . '[O]' . $colorReset; // Visited room
                } else {
                    // For adjacent unvisited rooms, verify they are actually accessible
                    // by checking if the player can move directly to them

                    // Find the direction from current position to this room
                    $directionToRoom = $this->getDirectionBetweenPositions($currentPosition, $position);

                    if ($directionToRoom !== null && $dungeon->canMove($currentPosition, $directionToRoom)) {
                        $output .= $colorAdjacent . '[ ]' . $colorReset; // Adjacent and accessible
                    } else {
                        // Adjacent but not directly accessible (show as unexplored)
                        $output .= $colorUnexplored . ' · ' . $colorReset;
                    }
                }
            }
            $output .= "\n";
        }

        return $output;
    }

    /**
     * Gets the direction from one position to another, if they are adjacent.
     *
     * @param Position $from Starting position
     * @param Position $to Target position
     * @return Direction|null Direction if positions are adjacent, null otherwise
     */
    private function getDirectionBetweenPositions(Position $from, Position $to): ?Direction
    {
        $directions = Direction::cases();
        foreach ($directions as $direction) {
            try {
                $movedPosition = $from->move($direction);
                if ($movedPosition->equals($to)) {
                    return $direction;
                }
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }
        return null;
    }

    /**
     * Helper method to check if a position is adjacent to any visited room.
     *
     * @param Dungeon $dungeon The dungeon
     * @param Position $position The position to check
     * @return bool True if adjacent to a visited room
     */
    private function isAdjacentToVisited(Dungeon $dungeon, Position $position): bool
    {
        $directions = Direction::cases();
        foreach ($directions as $direction) {
            try {
                $adjacentPosition = $position->move($direction);
                $adjacentRoom = $dungeon->getRoomAt($adjacentPosition);
                if ($adjacentRoom !== null && $adjacentRoom->isVisited()) {
                    return true;
                }
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }
        return false;
    }
}