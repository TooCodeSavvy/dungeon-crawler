<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\ValueObject;


/**
 * Value object representing the result of a combat action.
 */
final class CombatResult
{
    private function __construct(
        private readonly bool $successful,
        private readonly string $message,
        private readonly ?string $attackerName = null,
        private readonly ?string $defenderName = null,
        private readonly int $damage = 0,
        private readonly int $experienceGained = 0,
        private readonly bool $victory = false,
        private readonly bool $defeat = false,
        private readonly bool $dodged = false,
        private readonly int $defenderHealthRemaining = 0,
        private readonly int $defenderMaxHealth = 0
    ) {}

    public static function hit(
        string $attackerName,
        string $defenderName,
        int $damage,
        string $message,
        int $defenderHealthRemaining,
        int $defenderMaxHealth
    ): self {
        return new self(
            successful: true,
            message: $message,
            attackerName: $attackerName,
            defenderName: $defenderName,
            damage: $damage,
            defenderHealthRemaining: $defenderHealthRemaining,
            defenderMaxHealth: $defenderMaxHealth
        );
    }

    public static function victory(
        string $attackerName,
        string $defenderName,
        int $damage,
        string $message,
        int $experienceGained,
        int $defenderHealthRemaining,
        int $defenderMaxHealth
    ): self {
        return new self(
            successful: true,
            message: $message,
            attackerName: $attackerName,
            defenderName: $defenderName,
            damage: $damage,
            experienceGained: $experienceGained,
            victory: true,
            defenderHealthRemaining: $defenderHealthRemaining,
            defenderMaxHealth: $defenderMaxHealth
        );
    }

    public static function defeat(
        string $attackerName,
        string $defenderName,
        int $damage,
        string $message,
        int $defenderHealthRemaining,
        int $defenderMaxHealth
    ): self {
        return new self(
            successful: true,
            message: $message,
            attackerName: $attackerName,
            defenderName: $defenderName,
            damage: $damage,
            defeat: true,
            defenderHealthRemaining: $defenderHealthRemaining,
            defenderMaxHealth: $defenderMaxHealth
        );
    }

    public static function dodged(
        string $attackerName,
        string $defenderName,
        string $message
    ): self {
        return new self(
            successful: true,
            message: $message,
            attackerName: $attackerName,
            defenderName: $defenderName,
            dodged: true
        );
    }

    public static function exchange(
        int $playerDamageDealt,
        int $monsterDamageDealt,
        string $message,
        Health $playerHealth,
        Health $monsterHealth
    ): self {
        return new self(
            successful: true,
            message: $message,
            damage: $playerDamageDealt,
            defenderHealthRemaining: $monsterHealth->getValue(),
            defenderMaxHealth: $monsterHealth->getMax()
        );
    }

    public static function error(string $message): self
    {
        return new self(
            successful: false,
            message: $message
        );
    }

    // Getters
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDamage(): int
    {
        return $this->damage;
    }

    public function getExperienceGained(): int
    {
        return $this->experienceGained;
    }

    public function isVictory(): bool
    {
        return $this->victory;
    }

    public function isDefeat(): bool
    {
        return $this->defeat;
    }

    public function isDodged(): bool
    {
        return $this->dodged;
    }

    public function getDefenderHealthPercentage(): float
    {
        if ($this->defenderMaxHealth === 0) {
            return 0;
        }

        return ($this->defenderHealthRemaining / $this->defenderMaxHealth) * 100;
    }
}