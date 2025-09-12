<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Entity\Treasure;

final class TakeCommand implements CommandInterface
{
    private string $itemName;

    public function __construct(string $itemName = 'all')
    {
        $this->itemName = strtolower(trim($itemName));
    }

    public function execute(Game $game): CommandResult
    {
        $room = $game->getCurrentRoom();
        $player = $game->getPlayer();

        // Check if there's any treasure in the room
        if (!$room->hasTreasure()) {
            return CommandResult::failure("There's no treasure here to take.");
        }

        $availableTreasures = $room->getTreasures();
        $treasuresToTake = [];
        $messages = [];

        // Determine which treasures to take
        if ($this->itemName === 'all' || $this->itemName === '') {
            // Take all treasures
            $treasuresToTake = $availableTreasures;
            $room->removeAllTreasures();
        } else {
            // Try to find specific treasure
            $found = false;
            foreach ($availableTreasures as $key => $treasure) {
                if ($this->matchesTreasure($treasure, $this->itemName)) {
                    $treasuresToTake[] = $treasure;
                    $room->removeTreasure($key);
                    $found = true;
                    break; // Take only first matching item
                }
            }

            if (!$found) {
                // List available items if not found
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

        // Process each treasure
        $totalValue = 0;
        $totalHealth = 0;
        $weaponUpgrade = 0;

        foreach ($treasuresToTake as $treasure) {
            // Add to inventory
            $player->addToInventory($treasure);

            // Apply immediate effects based on treasure type
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

        // Add score for collecting treasure
        $game->addScore($totalValue);

        // Build success message
        $mainMessage = $this->buildSuccessMessage($treasuresToTake, $totalValue);

        if (!empty($messages)) {
            $mainMessage .= "\n" . implode("\n", $messages);
        }

        // Add inventory status if not too many items
        if (count($player->getInventory()) <= 10) {
            $mainMessage .= $this->getInventoryStatus($player);
        }

        return CommandResult::success($mainMessage);
    }

    public function canExecute(Game $game): bool
    {
        // Can take items as long as player is alive and not in active combat
        return $game->getPlayer()->isAlive() && !$game->isInCombat();
    }

    public function getName(): string
    {
        return 'take';
    }

    private function matchesTreasure(Treasure $treasure, string $itemName): bool
    {
        $treasureName = strtolower($treasure->getName());
        $treasureType = strtolower($treasure->getType()->value);

        // Check exact match first
        if ($treasureName === $itemName || $treasureType === $itemName) {
            return true;
        }

        // Check partial match
        if (str_contains($treasureName, $itemName)) {
            return true;
        }

        // Check common aliases
        $aliases = $this->getItemAliases($treasure);
        return in_array($itemName, $aliases, true);
    }

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

    private function applyTreasureEffect(Treasure $treasure, \DungeonCrawler\Domain\Entity\Player $player): array
    {
        $effect = ['health' => 0, 'attack' => 0];

        switch ($treasure->getType()->value) {
            case 'Health Potion':
                // Immediate healing effect
                $healAmount = $this->calculateHealAmount($treasure->getValue());
                $player->heal($healAmount);
                $effect['health'] = $healAmount;
                break;

            case 'Weapon':
                // Permanent attack boost
                $attackBoost = $this->calculateAttackBoost($treasure->getValue());
                $player->increaseAttackPower($attackBoost);
                $effect['attack'] = $attackBoost;
                break;

            case 'Gold':
            case 'Artifact':
                // These provide score only, no immediate effect
                break;
        }

        return $effect;
    }

    private function calculateHealAmount(int $treasureValue): int
    {
        // Heal amount based on treasure value
        return min(50, max(10, (int)($treasureValue / 2)));
    }

    private function calculateAttackBoost(int $treasureValue): int
    {
        // Attack boost based on treasure value
        return min(10, max(2, (int)($treasureValue / 10)));
    }

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

    private function getInventoryStatus(\DungeonCrawler\Domain\Entity\Player $player): string
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
            switch ($item->getType()->value) {
                case 'Gold':
                    $goldCount += $item->getValue();
                    break;
                case 'Health Potion':
                    $potionCount++;
                    break;
                case 'Weapon':
                    $weaponCount++;
                    break;
                case 'Artifact':
                    $artifactCount++;
                    break;
            }
        }

        $status = ["\n📋 Inventory Summary:"];
        if ($goldCount > 0) $status[] = "  💰 Gold: $goldCount";
        if ($potionCount > 0) $status[] = "  🧪 Potions: $potionCount";
        if ($weaponCount > 0) $status[] = "  ⚔️ Weapons: $weaponCount";
        if ($artifactCount > 0) $status[] = "  🏺 Artifacts: $artifactCount";

        return implode("\n", $status);
    }

    public function getItemName(): string
    {
        return $this->itemName;
    }
}