<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\ValueObject;

/**
 * Value object representing a complete combat round with multiple actions.
 */
class CombatRound
{
    /**
     * @param array<CombatResult> $actions All combat actions in this round
     * @param Health $playerHealth Player's health after the round
     * @param Health $monsterHealth Monster's health after the round
     * @param bool $combatEnded Whether combat has ended
     */
    public function __construct(
        private readonly array $actions,
        private readonly Health $playerHealth,
        private readonly Health $monsterHealth,
        private readonly bool $combatEnded
    ) {}

    /**
     * @return array<CombatResult>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    public function getPlayerHealth(): Health
    {
        return $this->playerHealth;
    }

    public function getMonsterHealth(): Health
    {
        return $this->monsterHealth;
    }

    public function isCombatEnded(): bool
    {
        return $this->combatEnded;
    }

    public function isPlayerVictorious(): bool
    {
        return $this->combatEnded && !$this->monsterHealth->isDead();
    }

    public function isPlayerDefeated(): bool
    {
        return $this->combatEnded && $this->playerHealth->isDead();
    }

    /**
     * Gets a formatted summary of the round for display.
     */
    public function getSummary(): string
    {
        $messages = array_map(
            fn(CombatResult $result) => $result->getMessage(),
            $this->actions
        );

        return implode("\n", $messages);
    }
}