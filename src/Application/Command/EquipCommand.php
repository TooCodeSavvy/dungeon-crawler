<?php
declare(strict_types=1);
namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Entity\Item;
use DungeonCrawler\Domain\Entity\Treasure;
use DungeonCrawler\Domain\Entity\TreasureType;

/**
 * Command to equip a weapon from the player's inventory.
 */
class EquipCommand implements CommandInterface
{
    /**
     * @var string The name of the weapon to equip
     */
    private string $itemName;

    /**
     * Constructor.
     *
     * @param string $itemName Name of the weapon to equip
     */
    public function __construct(string $itemName)
    {
        $this->itemName = strtolower(trim($itemName));
    }

    /**
     * Executes the equip command.
     *
     * @param ?Game $game The current game state.
     * @return CommandResult Result of the equip action.
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

        // Find the weapon in the inventory
        $weaponToEquip = null;

        foreach ($inventory as $item) {
            $isWeapon = false;

            // Check if it's a weapon regardless of item type
            if ($item instanceof Treasure && $item->getType() === TreasureType::WEAPON) {
                $isWeapon = true;
            } elseif ($item instanceof Item && $item->getType() === 'weapon') {
                $isWeapon = true;
            }

            // If it's a weapon and name matches search term
            if ($isWeapon && stripos($item->getName(), $this->itemName) !== false) {
                $weaponToEquip = $item;
                break;
            }
        }

        if ($weaponToEquip === null) {
            return CommandResult::failure(
                sprintf("You don't have a weapon named '%s' in your inventory.", $this->itemName)
            );
        }

        // Calculate attack bonus based on weapon value
        $attackBonus = $this->calculateWeaponBonus($weaponToEquip->getValue());

        // Equip the weapon
        $oldWeapon = $player->getEquippedWeapon();
        $player->equipWeapon($weaponToEquip, $attackBonus);

        // Create result message
        $message = sprintf("You equip the %s.", $weaponToEquip->getName());

        if ($oldWeapon !== null) {
            $message .= sprintf(" You unequip the %s.", $oldWeapon->getName());
        }

        $message .= sprintf(" Your attack power is now %d.", $player->getAttackPower());

        return CommandResult::success($message);
    }

    /**
     * Checks if the command can be executed.
     *
     * @param ?Game $game Current game instance.
     * @return bool True if the player is alive and has weapons.
     */
    public function canExecute(?Game $game): bool
    {
        if ($game === null) {
            return false;
        }

        if (!$game->getPlayer()->isAlive()) {
            return false;
        }

        // Check if player has any weapons
        foreach ($game->getPlayer()->getInventory() as $item) {
            if (($item instanceof Treasure && $item->getType() === TreasureType::WEAPON) ||
                ($item instanceof Item && $item->getType() === 'weapon')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the command name.
     *
     * @return string Command name.
     */
    public function getName(): string
    {
        return 'equip';
    }

    /**
     * Calculates weapon attack bonus based on value.
     *
     * @param int $weaponValue The gold value of the weapon
     * @return int The attack bonus provided
     */
    private function calculateWeaponBonus(int $weaponValue): int
    {
        // Simple formula: higher value = higher bonus
        // Value / 5 with minimum of 2
        return max(2, intval($weaponValue / 5));
    }
}