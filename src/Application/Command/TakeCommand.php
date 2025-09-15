<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Entity\Player;
use DungeonCrawler\Domain\Entity\Treasure;
use DungeonCrawler\Domain\Entity\TreasureType;

/**
 * Command to take one or more treasures from the current room.
 *
 * Supports taking a specific item by name/type or all available treasures.
 * Applies immediate effects of treasures such as healing or weapon upgrades,
 * updates player inventory and game score, and generates appropriate messages.
 */
class TakeCommand implements CommandInterface
{
    /**
     * @var string Name or alias of the item to take, or 'all' to take everything.
     */
    private string $itemName;

    /**
     * Constructor.
     *
     * @param string $itemName Item name or alias to take, defaults to 'all'.
     */
    public function __construct(string $itemName = 'all')
    {
        $this->itemName = strtolower(trim($itemName));
    }

    /**
     * Executes the take command.
     *
     * @param ?Game $game Current game instance.
     * @return CommandResult Result object with success or failure and messages.
     */
    public function execute(?Game $game): CommandResult
    {
        if ($game === null) {
            return new CommandResult(false, "Game is not initialized.");
        }
        $room = $game->getCurrentRoom();
        $player = $game->getPlayer();
        // Check if there is any treasure available in the room
        if (!$room->hasTreasure()) {
            return CommandResult::failure("There's no treasure here to take.");
        }

        // Determine treasures to take: all or specific item
        if ($this->itemName === 'all' || $this->itemName === '') {
            // Take all treasures
            $treasuresToTake = $room->takeTreasure(null);
        } else {
            // Take specific treasure(s) matching the name
            $treasuresToTake = $room->takeTreasure($this->itemName);

            if (empty($treasuresToTake)) {
                // Item not found, inform player with list of available items
                $availableTreasures = $room->getTreasures();
                $itemList = $this->formatAvailableItems($availableTreasures);
                return CommandResult::failure(
                    sprintf(
                        "Cannot find '%s'. Available items: %s\nUse 'take all' to take everything.",
                        $this->itemName,
                        $itemList
                    )
                );
            }
        }

        $totalValue = 0;
        $totalHealth = 0;
        $weaponUpgrade = 0;
        $messages = [];

        // Process effects and add treasures to inventory
        foreach ($treasuresToTake as $treasure) {
            $player->addToInventory($treasure);
            $effect = $this->applyTreasureEffect($treasure, $player);
            if ($effect['health'] > 0) {
                $totalHealth += $effect['health'];
                $messages[] = sprintf(
                    "💚 %s restored %d health!",
                    $treasure->getName(),
                    $effect['health']
                );
            }
            if ($effect['attack'] > 0) {
                $weaponUpgrade += $effect['attack'];
                $messages[] = sprintf(
                    "⚔️ %s increased your attack power by %d!",
                    $treasure->getName(),
                    $effect['attack']
                );
            }
            $totalValue += $treasure->getValue();
        }

        // Update game score with total treasure value
        $game->addScore($totalValue);

        // Build the main success message about the treasures taken
        $mainMessage = $this->buildSuccessMessage($treasuresToTake, $totalValue);

        // Append additional effect messages if any
        if (!empty($messages)) {
            $mainMessage .= "\n" . implode("\n", $messages);
        }

        // Show inventory summary if not too large
        if (count($player->getInventory()) <= 10) {
            $mainMessage .= $this->getInventoryStatus($player);
        }

        return CommandResult::success($mainMessage);
    }

    /**
     * Determines if the command can currently be executed.
     *
     * @param ?Game $game Current game instance.
     * @return bool True if player is alive and not in combat.
     */
    public function canExecute(?Game $game): bool
    {
        // Handle null game case
        if ($game === null) {
            return false;
        }

        return $game->getPlayer()->isAlive() && !$game->isInCombat();
    }

    /**
     * Returns the command name.
     *
     * @return string Command name.
     */
    public function getName(): string
    {
        return 'take';
    }

    /**
     * Checks if the given treasure matches the requested item name or alias.
     *
     * @param Treasure $treasure Treasure to check.
     * @param string $itemName Lowercased item name or alias to match.
     * @return bool True if matches, false otherwise.
     */
    private function matchesTreasure(Treasure $treasure, string $itemName): bool
    {
        $treasureName = strtolower($treasure->getName());
        $treasureType = strtolower($treasure->getType()->value);

        // Exact match on name or type
        if ($treasureName === $itemName || $treasureType === $itemName) {
            return true;
        }

        // Partial match in name
        if (str_contains($treasureName, $itemName)) {
            return true;
        }

        // Check against common aliases for treasure types
        $aliases = $this->getItemAliases($treasure);
        return in_array($itemName, $aliases, true);
    }

    /**
     * Returns common aliases for treasure types.
     *
     * @param Treasure $treasure Treasure to get aliases for.
     * @return array List of alias strings.
     */
    private function getItemAliases(Treasure $treasure): array
    {
        $type = $treasure->getType()->value;

        return match($type) {
            'Gold' => ['gold', 'coins', 'money', 'gp'],
            'Health Potion' => ['potion', 'health', 'healing', 'hp'],
            'Weapon' => ['weapon', 'sword', 'blade', 'arms'],
            'Artifact' => ['artifact', 'relic', 'ancient'],
            default => []
        };
    }

    /**
     * Applies immediate effects of a treasure to the player.
     *
     * @param Treasure $treasure Treasure whose effect to apply.
     * @param Player $player Player to apply effects on.
     * @return array Associative array with 'health' and 'attack' keys indicating effect magnitudes.
     */
    private function applyTreasureEffect(Treasure $treasure, Player $player): array
    {
        $effect = ['health' => 0, 'attack' => 0];

        switch ($treasure->getType()->value) {
            case 'Health Potion':
                // Heal player immediately
                $healAmount = $this->calculateHealAmount($treasure->getValue());
                $player->heal($healAmount);
                $effect['health'] = $healAmount;
                break;

            case 'Weapon':
                // Increase player's attack power permanently
                $attackBoost = $this->calculateAttackBoost($treasure->getValue());
                $player->increaseAttackPower($attackBoost);
                $effect['attack'] = $attackBoost;
                break;

            case 'Gold':
            case 'Artifact':
                // No immediate effect; score added separately
                break;
        }

        return $effect;
    }

    /**
     * Calculates healing amount from treasure value.
     *
     * @param int $treasureValue Treasure's numeric value.
     * @return int Amount of health restored.
     */
    private function calculateHealAmount(int $treasureValue): int
    {
        return min(50, max(10, (int)($treasureValue / 2)));
    }

    /**
     * Calculates attack boost amount from treasure value.
     *
     * @param int $treasureValue Treasure's numeric value.
     * @return int Attack power increase amount.
     */
    private function calculateAttackBoost(int $treasureValue): int
    {
        return min(10, max(2, (int)($treasureValue / 10)));
    }

    /**
     * Formats available treasures into a readable string.
     *
     * @param Treasure[] $treasures List of treasures.
     * @return string Formatted list of items with icons.
     */
    private function formatAvailableItems(array $treasures): string
    {
        if (empty($treasures)) {
            return 'none';
        }

        $items = array_map(
            fn(Treasure $t) => sprintf("%s (%s)", $t->getName(), $t->getType()->getIcon()),
            $treasures
        );

        return implode(', ', $items);
    }

    /**
     * Builds a success message describing the treasures taken.
     *
     * @param Treasure[] $treasures Treasures taken.
     * @param int $totalValue Total value of taken treasures.
     * @return string Success message.
     */
    private function buildSuccessMessage(array $treasures, int $totalValue): string
    {
        $count = count($treasures);

        if ($count === 1) {
            $treasure = $treasures[0];
            return sprintf(
                "📦 You take %s %s worth %d gold. (+%d points)",
                $treasure->getName(),
                $treasure->getType()->getIcon(),
                $treasure->getValue(),
                $totalValue
            );
        }

        $itemList = array_map(
            fn(Treasure $t) => sprintf("%s %s", $t->getName(), $t->getType()->getIcon()),
            $treasures
        );

        return sprintf(
            "📦 You take %d items:\n%s\nTotal value: %d gold (+%d points)",
            $count,
            implode("\n", array_map(fn($item) => "  • " . $item, $itemList)),
            $totalValue,
            $totalValue
        );
    }

    /**
     * Returns a summary of the player's current inventory.
     *
     * @param Player $player Player whose inventory to summarize.
     * @return string Inventory summary message.
     */
    private function getInventoryStatus(Player $player): string
    {
        $inventory = $player->getInventory();
        if (empty($inventory)) {
            return "";
        }

        $goldCount = 0;
        $potionCount = 0;
        $weaponCount = 0;
        $artifactCount = 0;

        foreach ($inventory as $item) {
            if ($item instanceof Treasure) {
                // Handle Treasure objects
                $type = $item->getType();
                switch ($type) {
                    case TreasureType::GOLD:
                        $goldCount += $item->getValue();
                        break;
                    case TreasureType::HEALTH_POTION:
                        $potionCount++;
                        break;
                    case TreasureType::WEAPON:
                        $weaponCount++;
                        break;
                    case TreasureType::ARTIFACT:
                        $artifactCount++;
                        break;
                }
            } else {
                // Handle Item objects
                $type = $item->getType();
                switch ($type) {
                    case 'gold':
                        $goldCount += $item->getValue();
                        break;
                    case 'potion':
                        $potionCount++;
                        break;
                    case 'weapon':
                        $weaponCount++;
                        break;
                    case 'artifact':
                        $artifactCount++;
                        break;
                }
            }
        }

        $status = ["\n📋 Inventory Summary:"];
        if ($goldCount > 0) $status[] = "  💰 Gold: $goldCount";
        if ($potionCount > 0) $status[] = "  🧪 Potions: $potionCount";
        if ($weaponCount > 0) $status[] = "  ⚔️ Weapons: $weaponCount";
        if ($artifactCount > 0) $status[] = "  🏺 Artifacts: $artifactCount";

        return implode("\n", $status);
    }

    /**
     * Returns the requested item name or alias.
     *
     * @return string Item name.
     */
    public function getItemName(): string
    {
        return $this->itemName;
    }
}
