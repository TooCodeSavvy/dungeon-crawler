<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

/**
 * Debug command to show room information and available directions.
 */
class DebugCommand implements CommandInterface
{
    /**
     * @inheritDoc
     */
    public function execute(?Game $game): CommandResult
    {
        if ($game === null) {
            return new CommandResult(false, "No active game");
        }

        $room = $game->getCurrentRoom();
        $pos = $game->getCurrentPosition();

        $message = "Debug Information:\n";
        $message .= "Current Position: " . $pos->toString() . "\n";
        $message .= "Room UUID: " . $room->getId() . "\n";
        $message .= "Room Description: " . $room->getDescription() . "\n";
        $message .= "Room is Exit: " . ($room->isExit() ? "Yes" : "No") . "\n";
        $message .= "Room is Visited: " . ($room->isVisited() ? "Yes" : "No") . "\n\n";

        $message .= "Available Connections:\n";

        // Get Direction enum cases
        $directions = \DungeonCrawler\Domain\ValueObject\Direction::cases();

        foreach ($directions as $dir) {
            $message .= "- " . $dir->value . ": " .
                ($room->hasConnection($dir) ? "Connected" : "Blocked") . "\n";
        }

        $message .= "\nDungeon Info:\n";
        $message .= "Width: " . $game->getDungeon()->getWidth() . "\n";
        $message .= "Height: " . $game->getDungeon()->getHeight() . "\n";
        $message .= "Entrance: " . $game->getDungeon()->getEntrancePosition()->toString() . "\n";
        $message .= "Exit: " . $game->getDungeon()->getExitPosition()->toString() . "\n";

        return new CommandResult(true, $message);
    }

    /**
     * @inheritDoc
     */
    public function canExecute(Game $game): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'debug';
    }
}