<?php
declare(strict_types=1);
namespace DungeonCrawler\Infrastructure\Console;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Entity\Room;

/**
 * Responsible for rendering game output to the console.
 *
 * Provides methods to display game status, rooms, messages, and UI elements
 * using ANSI escape codes for color and formatting to enhance the console experience.
 */
final class ConsoleRenderer
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
     * Renders the complete game UI with optional action result message.
     *
     * @param ?Game $game The current game state
     * @param string|null $actionResult The result of the player's last action
     */
    public function renderGameUI(?Game $game, ?string $actionResult = null): void
    {
        $this->clear();

        // Only proceed with game-specific rendering if we have a game
        if ($game === null) {
            // Handle null game case - perhaps show a message
            echo self::COLOR_RED . "No active game. Please start or load a game." . self::COLOR_RESET . "\n";
            return;
        }

        $this->renderGameStatus($game);
        $this->renderRoom($game->getCurrentRoom());

        // If there's an action result, display it in a visually distinct section
        if ($actionResult !== null && trim($actionResult) !== '') {
            $this->renderActionResult($actionResult);
        }

        // Get available actions for the current game state
        $actions = [
            'move <direction>',
            'map',
            'inventory',
            'attack',
            'flee',
            'save',
            'quit',
            'help'
        ];

        if ($game->getCurrentRoom()->hasTreasure()) {
            $actions[] = 'take <item|all>';
        }

        if ($game->getCurrentRoom()->hasMonster() || $game->isPathBlocked()) {
            $actions[] = 'attack';
        }

        $this->renderAvailableActions($actions);
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
     * Renders the dungeon map with colors and styling.
     *
     * @param string $mapContent The generated map content to display
     */
    public function renderMap(string $mapContent): void
    {
        $this->clear();

        // Draw a styled border around the map
        echo self::COLOR_BOLD . self::COLOR_CYAN;
        echo "╔═══════════════ DUNGEON MAP ════════════════╗\n";
        echo "╚═══════════════════════════════════════════╝\n";
        echo self::COLOR_RESET;

        // Display the map content
        echo $mapContent;

        echo "\n" . self::COLOR_YELLOW . "Press Enter to continue..." . self::COLOR_RESET;
    }
}