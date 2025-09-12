<?php
declare(strict_types=1);

namespace DungeonCrawler\Infrastructure\Console;

use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Entity\Room;
use DungeonCrawler\Domain\Entity\Player;

final class ConsoleRenderer
{
    private const COLOR_RESET = "\033[0m";
    private const COLOR_RED = "\033[31m";
    private const COLOR_GREEN = "\033[32m";
    private const COLOR_YELLOW = "\033[33m";
    private const COLOR_BLUE = "\033[34m";
    private const COLOR_MAGENTA = "\033[35m";
    private const COLOR_CYAN = "\033[36m";
    private const COLOR_WHITE = "\033[37m";
    private const COLOR_BOLD = "\033[1m";

    public function clear(): void
    {
        echo "\033[2J\033[H";
    }

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

    public function renderRoom(Room $room): void
    {
        echo self::COLOR_CYAN . "📍 " . $room->getName() . self::COLOR_RESET . "\n";
        echo $room->getDescription() . "\n\n";

        if ($room->hasMonster()) {
            $monster = $room->getMonster();
            echo self::COLOR_RED . "⚔️  A " . $monster->getName() . " blocks your path!" . self::COLOR_RESET . "\n";
            echo $this->createHealthBar($monster->getHealth()->getValue(), $monster->getHealth()->getMax()) . "\n";
        }

        if ($room->hasTreasure()) {
            echo self::COLOR_YELLOW . "✨ You see treasure here!" . self::COLOR_RESET . "\n";
            foreach ($room->getTreasures() as $treasure) {
                echo "   • " . $treasure->getDisplayInfo() . "\n";
            }
        }

        if ($room->isExit()) {
            echo self::COLOR_GREEN . "🚪 This is the exit!" . self::COLOR_RESET . "\n";
        }

        echo "\n";
    }

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

    public function renderAvailableActions(array $actions): void
    {
        echo self::COLOR_BOLD . "Available Actions:" . self::COLOR_RESET . "\n";
        foreach ($actions as $action) {
            echo "  • " . $action . "\n";
        }
        echo "\n";
    }

    public function renderError(string $message): void
    {
        echo self::COLOR_RED . "❌ Error: " . $message . self::COLOR_RESET . "\n\n";
    }

    public function renderSuccess(string $message): void
    {
        echo self::COLOR_GREEN . "✓ " . $message . self::COLOR_RESET . "\n\n";
    }

    public function renderMessage(string $message): void
    {
        echo $message . "\n\n";
    }

    public function renderPrompt(): void
    {
        echo self::COLOR_BOLD . "> " . self::COLOR_RESET;
    }

    private function renderBorder(): void
    {
        echo str_repeat("═", 45) . "\n";
    }
}