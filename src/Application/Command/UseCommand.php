<?php
declare(strict_types=1);
namespace DungeonCrawler\Application\Command;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Entity\Item;
use DungeonCrawler\Domain\Entity\Player;
use DungeonCrawler\Domain\Entity\Treasure;
use DungeonCrawler\Domain\Entity\TreasureType;
/**
 * Command to use an item from the player's inventory.
 */
class UseCommand implements CommandInterface
{
    /**
     * @var string Name of the item to use
     */
    private string $itemName;

    /**
     * Constructor.
     *
     * @param string $itemName Name of the item to use
     */
    public function __construct(string $itemName)
    {
        $this->itemName = strtolower(trim($itemName));
    }

    /**
     * Executes the use command.
     *
     * @param ?Game $game Current game instance.
     * @return CommandResult Result of using the item.
     */
    public function execute(?Game $game): CommandResult
    {
        if ($game === null) {
            return CommandResult::failure("No active game.");
        }

        $player = $game->getPlayer();
        $inventory = $player->getInventory();

        if (empty($inventory)) {
            return CommandResult::failure("You have no items in your inventory.");
        }

        // Find the item in the inventory
        $itemIndex = null;
        $itemToUse = null;

        foreach ($inventory as $index => $item) {
            $itemName = $item->getName();
            if (stripos($itemName, $this->itemName) !== false) {
                $itemIndex = $index;
                $itemToUse = $item;
                break;
            }
        }

        if ($itemToUse === null) {
            return CommandResult::failure(
                sprintf("You don't have '%s' in your inventory.", $this->itemName)
            );
        }

        // Handle the item based on its type
        if ($itemToUse instanceof Treasure) {
            return $this->useTreasure($game, $player, $itemToUse, $itemIndex);
        } else if ($itemToUse instanceof Item) {
            return $this->useItem($game, $player, $itemToUse, $itemIndex);
        }

        return CommandResult::failure("Cannot use this type of item.");
    }

    /**
     * Uses a Treasure item.
     *
     * @param Game $game The current game
     * @param Player $player The player
     * @param Treasure $treasure The treasure to use
     * @param int $itemIndex The index of the item in inventory
     * @return CommandResult The result of using the treasure
     */
    private function useTreasure(Game $game, Player $player, Treasure $treasure, int $itemIndex): CommandResult
    {
        $type = $treasure->getType();

        switch ($type) {
            case TreasureType::HEALTH_POTION:
                // Get player's current health
                $health = $player->getHealth();
                $beforeHealth = $health->getValue();

                // Calculate healing amount (you may want to adjust this)
                $healAmount = $treasure->getValue();

                // Apply healing
                $player->heal($healAmount);

                // Calculate actual healing (in case player was already at max health)
                $afterHealth = $player->getHealth()->getValue();
                $actualHealing = $afterHealth - $beforeHealth;

                // Remove the potion from inventory
                $inventory = $player->getInventory();
                unset($inventory[$itemIndex]);
                $player->setInventory(array_values($inventory));

                if ($actualHealing > 0) {
                    return CommandResult::success(
                        sprintf(
                            "You drink the %s and restore %d health points! (Health: %d/%d)",
                            $treasure->getName(),
                            $actualHealing,
                            $afterHealth,
                            $health->getMax()
                        )
                    );
                } else {
                    return CommandResult::success(
                        sprintf("You drink the %s but you're already at full health.", $treasure->getName())
                    );
                }

            default:
                return CommandResult::failure(
                    sprintf("You can't use %s. It's not a usable item.", $treasure->getName())
                );
        }
    }

    /**
     * Uses an Item.
     *
     * @param Game $game The current game
     * @param Player $player The player
     * @param Item $item The item to use
     * @param int $itemIndex The index of the item in inventory
     * @return CommandResult The result of using the item
     */
    private function useItem(Game $game, Player $player, Item $item, int $itemIndex): CommandResult
    {
        $type = $item->getType();

        switch ($type) {
            case 'potion':
                // Get player's current health
                $health = $player->getHealth();
                $beforeHealth = $health->getValue();

                // Calculate healing amount (adjust as needed)
                $healAmount = $item->getValue();

                // Apply healing
                $player->heal($healAmount);

                // Calculate actual healing
                $afterHealth = $player->getHealth()->getValue();
                $actualHealing = $afterHealth - $beforeHealth;

                // Remove the potion from inventory
                $inventory = $player->getInventory();
                unset($inventory[$itemIndex]);
                $player->setInventory(array_values($inventory));

                if ($actualHealing > 0) {
                    return CommandResult::success(
                        sprintf(
                            "You use the %s and restore %d health points! (Health: %d/%d)",
                            $item->getName(),
                            $actualHealing,
                            $afterHealth,
                            $health->getMax()
                        )
                    );
                } else {
                    return CommandResult::success(
                        sprintf("You use the %s but you're already at full health.", $item->getName())
                    );
                }

            default:
                return CommandResult::failure(
                    sprintf("You can't use %s. It's not a usable item.", $item->getName())
                );
        }
    }

    /**
     * Checks if the command can be executed.
     *
     * @param ?Game $game Current game instance.
     * @return bool True if the player is alive and has items.
     */
    public function canExecute(?Game $game): bool
    {
        if ($game === null) {
            return false;
        }

        return $game->getPlayer()->isAlive() && count($game->getPlayer()->getInventory()) > 0;
    }

    /**
     * Returns the command name.
     *
     * @return string Command name.
     */
    public function getName(): string
    {
        return 'use';
    }

    /**
     * Returns the name of the item to use.
     *
     * @return string Item name.
     */
    public function getItemName(): string
    {
        return $this->itemName;
    }
}