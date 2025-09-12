<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Service;

use DungeonCrawler\Domain\ValueObject\Health;

/**
 * Value object containing combat statistics for display.
 */
final class CombatStats
{
    public function __construct(
        private readonly string $playerName,
        private readonly Health $playerHealth,
        private readonly int $playerAttackPower,
        private readonly string $monsterName,
        private readonly Health $monsterHealth,
        private readonly int $monsterAttackPower,
        private readonly int $monsterExperienceReward
    ) {}

    /**
     * Gets a formatted display of combat stats.
     */
    public function getDisplay(): string
    {
        return sprintf(
            "⚔️ COMBAT STATUS ⚔️\n" .
            "═══════════════════\n" .
            "%s: %d/%d HP | Attack: %d\n" .
            "%s: %d/%d HP | Attack: %d | XP Reward: %d\n" .
            "═══════════════════",
            $this->playerName,
            $this->playerHealth->getValue(),
            $this->playerHealth->getMax(),
            $this->playerAttackPower,
            $this->monsterName,
            $this->monsterHealth->getValue(),
            $this->monsterHealth->getMax(),
            $this->monsterAttackPower,
            $this->monsterExperienceReward
        );
    }

    /**
     * Gets health bars for visual display.
     */
    public function getHealthBars(): array
    {
        return [
            'player' => $this->generateHealthBar($this->playerHealth),
            'monster' => $this->generateHealthBar($this->monsterHealth)
        ];
    }

    private function generateHealthBar(Health $health): string
    {
        $percentage = $health->getPercentage();
        $barLength = 20;
        $filledLength = (int)($barLength * $percentage / 100);
        $emptyLength = $barLength - $filledLength;

        $bar = str_repeat('█', $filledLength) . str_repeat('░', $emptyLength);

        // Color based on health percentage
        if ($percentage > 60) {
            $color = "\033[32m"; // Green
        } elseif ($percentage > 30) {
            $color = "\033[33m"; // Yellow
        } else {
            $color = "\033[31m"; // Red
        }

        return sprintf(
            "%s[%s]\033[0m %d/%d HP",
            $color,
            $bar,
            $health->getValue(),
            $health->getMax()
        );
    }

    // Getters for individual properties if needed
    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    public function getPlayerHealth(): Health
    {
        return $this->playerHealth;
    }

    public function getMonsterName(): string
    {
        return $this->monsterName;
    }

    public function getMonsterHealth(): Health
    {
        return $this->monsterHealth;
    }
}