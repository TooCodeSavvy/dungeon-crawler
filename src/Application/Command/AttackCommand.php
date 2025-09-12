<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;

class AttackCommand implements CommandInterface
{
    private ?string $target;

    public function __construct(?string $target = null)
    {
        $this->target = $target;
    }

    public function execute(Game $game): CommandResult
    {
        $room = $game->getCurrentRoom();

        // Check if there's a monster in the room
        if (!$room->hasMonster()) {
            return CommandResult::failure("There's nothing to attack here!");
        }

        $monster = $room->getMonster();
        $player = $game->getPlayer();

        // Validate target if specified
        if ($this->target !== null && !$this->isValidTarget($this->target, $monster->getName())) {
            return CommandResult::failure(
                sprintf("Cannot attack '%s'. The %s is your only target.",
                    $this->target,
                    $monster->getName()
                )
            );
        }

        // Perform the attack
        $playerDamage = $this->calculateDamage($player->getAttackPower());
        $monster->takeDamage($playerDamage);

        $messages = [];
        $messages[] = sprintf(
            "⚔️ You strike the %s for %d damage!",
            $monster->getName(),
            $playerDamage
        );

        // Check if monster is defeated
        if (!$monster->isAlive()) {
            $room->removeMonster();
            $game->endCombat();

            // Award points based on monster difficulty
            $points = $this->calculatePoints($monster);
            $game->addScore($points);

            $messages[] = sprintf(
                "💀 The %s has been defeated! You gain %d points!",
                $monster->getName(),
                $points
            );

            // Check for treasure drop
            if ($this->shouldDropTreasure()) {
                $treasure = $this->generateTreasureDrop($monster);
                $room->addTreasure($treasure);
                $messages[] = sprintf(
                    "✨ The %s dropped %s!",
                    $monster->getName(),
                    $treasure->getName()
                );
            }

            return CommandResult::success(implode("\n", $messages));
        }

        // Monster counter-attacks if still alive
        $monsterDamage = $this->calculateDamage($monster->getAttackPower());
        $player->takeDamage($monsterDamage);

        $messages[] = sprintf(
            "🔥 The %s strikes back for %d damage!",
            $monster->getName(),
            $monsterDamage
        );

        // Add health status
        $messages[] = $this->getHealthStatus($player, $monster);

        // Check if player died
        if (!$player->isAlive()) {
            $messages[] = "💔 You have been defeated...";
            return CommandResult::failure(implode("\n", $messages));
        }

        return CommandResult::success(implode("\n", $messages));
    }

    public function canExecute(Game $game): bool
    {
        // Can only attack during combat or when monster is present
        return $game->getPlayer()->isAlive() &&
            $game->getCurrentRoom()->hasMonster();
    }

    public function getName(): string
    {
        return 'attack';
    }

    private function isValidTarget(string $target, string $monsterName): bool
    {
        $normalizedTarget = strtolower(trim($target));
        $normalizedMonster = strtolower($monsterName);

        // Allow partial matching
        return str_contains($normalizedMonster, $normalizedTarget) ||
            $normalizedTarget === 'monster' ||
            $normalizedTarget === 'enemy';
    }

    private function calculateDamage(int $baseDamage): int
    {
        // Add some randomness to damage (80% to 120% of base)
        $variance = (int)($baseDamage * 0.2);
        $damage = rand($baseDamage - $variance, $baseDamage + $variance);

        // Critical hit chance (10%)
        if (rand(1, 100) <= 10) {
            $damage = (int)($damage * 1.5);
        }

        return max(1, $damage); // Ensure at least 1 damage
    }

    private function calculatePoints(\DungeonCrawler\Domain\Entity\Monster $monster): int
    {
        // Base points based on monster's max health
        $basePoints = $monster->getHealth()->getMax() * 2;

        // Bonus for attack power
        $bonusPoints = $monster->getAttackPower() * 3;

        return $basePoints + $bonusPoints;
    }

    private function shouldDropTreasure(): bool
    {
        // 30% chance to drop treasure
        return rand(1, 100) <= 30;
    }

    private function generateTreasureDrop(\DungeonCrawler\Domain\Entity\Monster $monster): \DungeonCrawler\Domain\Entity\Treasure
    {
        $factory = new \DungeonCrawler\Domain\Factory\TreasureFactory();

        // Stronger monsters drop better treasure
        $rarity = match(true) {
            $monster->getHealth()->getMax() >= 100 => 'rare',
            $monster->getHealth()->getMax() >= 50 => 'uncommon',
            default => 'common'
        };

        return $factory->createRandom($rarity);
    }

    private function getHealthStatus(
        \DungeonCrawler\Domain\Entity\Player $player,
        \DungeonCrawler\Domain\Entity\Monster $monster
    ): string {
        $playerHealth = $player->getHealth();
        $monsterHealth = $monster->getHealth();

        $playerBar = $this->createHealthBar(
            $playerHealth->getValue(),
            $playerHealth->getMax()
        );

        $monsterBar = $this->createHealthBar(
            $monsterHealth->getValue(),
            $monsterHealth->getMax()
        );

        return sprintf(
            "Your HP: %s %d/%d | %s HP: %s %d/%d",
            $playerBar,
            $playerHealth->getValue(),
            $playerHealth->getMax(),
            $monster->getName(),
            $monsterBar,
            $monsterHealth->getValue(),
            $monsterHealth->getMax()
        );
    }

    private function createHealthBar(int $current, int $max): string
    {
        $percentage = ($current / $max) * 100;
        $bars = 10;
        $filled = (int)(($percentage / 100) * $bars);

        return '[' . str_repeat('█', $filled) . str_repeat('░', $bars - $filled) . ']';
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }
}