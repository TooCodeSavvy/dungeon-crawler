<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Service;

use DungeonCrawler\Domain\ValueObject\Health;

/**
 * Value object encapsulating the key combat statistics for the player and monster.
 *
 * Provides formatted displays of combat stats, including health values and attack power,
 * and renders visual health bars with color coding to reflect current health status.
 */
class CombatStats
{
    /**
     * @param string $playerName Name of the player character
     * @param Health $playerHealth Current and max health of the player
     * @param int $playerAttackPower Player's attack strength
     * @param string $monsterName Name of the monster opponent
     * @param Health $monsterHealth Current and max health of the monster
     * @param int $monsterAttackPower Monster's attack strength
     * @param int $monsterExperienceReward Experience points rewarded for defeating the monster
     */
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
     * Generates a formatted string summarizing the current combat status.
     *
     * Displays player and monster names, health (current/max), attack power,
     * and monster experience reward in a neatly formatted block for console or UI display.
     *
     * @return string Formatted combat status message
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
     * Creates health bars for both player and monster to visualize health levels.
     *
     * Uses a 20-character bar, filled proportionally based on current health percentage.
     * Bars are color-coded (green/yellow/red) for quick health status recognition.
     *
     * @return array Associative array with 'player' and 'monster' keys containing health bar strings
     */
    public function getHealthBars(): array
    {
        return [
            'player' => $this->generateHealthBar($this->playerHealth),
            'monster' => $this->generateHealthBar($this->monsterHealth)
        ];
    }

    /**
     * Generates a single health bar string for a given Health object.
     *
     * Calculates the filled and empty segments of the bar according to health percentage,
     * then applies color coding:
     * - Green for >60% health
     * - Yellow for 31%-60%
     * - Red for 30% or less
     *
     * @param Health $health The health object containing current and max health
     *
     * @return string Colored health bar with numeric health display
     */
    private function generateHealthBar(Health $health): string
    {
        $percentage = $health->getPercentage();
        $barLength = 20;
        $filledLength = (int)($barLength * $percentage / 100);
        $emptyLength = $barLength - $filledLength;

        // Build the bar string with filled and empty blocks
        $bar = str_repeat('█', $filledLength) . str_repeat('░', $emptyLength);

        // Select color based on health percentage
        if ($percentage > 60) {
            $color = "\033[32m"; // Green
        } elseif ($percentage > 30) {
            $color = "\033[33m"; // Yellow
        } else {
            $color = "\033[31m"; // Red
        }

        // Return the colored bar with numeric health info
        return sprintf(
            "%s[%s]\033[0m %d/%d HP",
            $color,
            $bar,
            $health->getValue(),
            $health->getMax()
        );
    }

    /**
     * Gets the player's name.
     */
    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    /**
     * Gets the player's Health object.
     */
    public function getPlayerHealth(): Health
    {
        return $this->playerHealth;
    }

    /**
     * Gets the monster's name.
     */
    public function getMonsterName(): string
    {
        return $this->monsterName;
    }

    /**
     * Gets the monster's Health object.
     */
    public function getMonsterHealth(): Health
    {
        return $this->monsterHealth;
    }
}
