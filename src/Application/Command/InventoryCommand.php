<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

/**
 * Command to display the player's inventory contents.
 */
class InventoryCommand implements CommandInterface
{
    /**
     * Executes the inventory command, displaying the player's items.
     *
     * @param ?Game $game The current game instance.
     * @return CommandResult The result of executing the command.
     */
    public function execute(?Game $game): CommandResult
    {
        if ($game === null) {
            return new CommandResult(false, "No active game to display inventory for.");
        }

        $player = $game->getPlayer();
        $inventory = $player->getInventory();

        if (empty($inventory)) {
            return new CommandResult(true, "Your inventory is empty.");
        }

        // Format inventory items for display
        $items = [];
        $totalGold = 0;

        foreach ($inventory as $item) {
            if ($item->getType() === 'gold') {
                $totalGold += $item->getValue();
            } else {
                $items[] = sprintf(
                    "• %s - %s",
                    $item->getName(),
                    $item->getDescription()
                );
            }
        }

        $message = "Inventory Contents:\n";

        // Add gold if any
        if ($totalGold > 0) {
            $message .= sprintf("• Gold: %d coins\n", $totalGold);
        }

        // Add other items
        if (!empty($items)) {
            $message .= implode("\n", $items);
        } else if ($totalGold === 0) {
            $message = "Your inventory is empty.";
        }

        return new CommandResult(true, $message);
    }

    /**
     * Determines if the inventory command can be executed.
     *
     * This can always be executed when a game is active.
     *
     * @param Game $game The current game instance.
     * @return bool Always true as inventory can be viewed anytime.
     */
    public function canExecute(Game $game): bool
    {
        return true;
    }

    /**
     * Gets the name of the command.
     *
     * @return string The command name.
     */
    public function getName(): string
    {
        return 'inventory';
    }
}