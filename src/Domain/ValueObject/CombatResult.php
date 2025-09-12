<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\ValueObject;

/**
 * Value object representing the result of a combat action.
 *
 * Encapsulates detailed information about the outcome of a combat interaction,
 * including success status, damage dealt, health remaining, experience gained,
 * and specific flags such as victory, defeat, or dodged attacks.
 *
 * This immutable object provides static factory methods for creating
 * various types of combat results, enforcing clear intent and consistency
 * in representing combat outcomes throughout the domain.
 */
class CombatResult
{
    /**
     * Private constructor to enforce creation through named static factories.
     *
     * @param bool $successful Whether the combat action was successful.
     * @param string $message A descriptive message about the combat result.
     * @param string|null $attackerName Name of the attacker involved, if applicable.
     * @param string|null $defenderName Name of the defender involved, if applicable.
     * @param int $damage Amount of damage dealt during the action.
     * @param int $experienceGained Experience gained from the action (if any).
     * @param bool $victory Flag indicating if this result was a victory.
     * @param bool $defeat Flag indicating if this result was a defeat.
     * @param bool $dodged Flag indicating if the attack was dodged.
     * @param int $defenderHealthRemaining Health remaining for the defender after the action.
     * @param int $defenderMaxHealth Defender's maximum health value.
     */
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

    /**
     * Creates a CombatResult representing a successful hit.
     *
     * @param string $attackerName Name of the attacker.
     * @param string $defenderName Name of the defender.
     * @param int $damage Damage dealt in the hit.
     * @param string $message Description of the attack.
     * @param int $defenderHealthRemaining Defender's health after the hit.
     * @param int $defenderMaxHealth Defender's maximum health.
     *
     * @return self A CombatResult instance representing the hit.
     */
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

    /**
     * Creates a CombatResult representing a victory outcome.
     *
     * @param string $attackerName Name of the victorious attacker.
     * @param string $defenderName Name of the defeated defender.
     * @param int $damage Damage dealt to cause victory.
     * @param string $message Descriptive victory message.
     * @param int $experienceGained Experience points gained.
     * @param int $defenderHealthRemaining Defender's remaining health (usually 0).
     * @param int $defenderMaxHealth Defender's maximum health.
     *
     * @return self A CombatResult instance representing the victory.
     */
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

    /**
     * Creates a CombatResult representing a defeat outcome.
     *
     * @param string $attackerName Name of the attacker who caused defeat.
     * @param string $defenderName Name of the defeated defender.
     * @param int $damage Damage dealt causing defeat.
     * @param string $message Defeat message.
     * @param int $defenderHealthRemaining Defender's remaining health (usually 0).
     * @param int $defenderMaxHealth Defender's maximum health.
     *
     * @return self A CombatResult instance representing the defeat.
     */
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

    /**
     * Creates a CombatResult representing a dodged attack.
     *
     * @param string $attackerName Name of the attacker whose attack was dodged.
     * @param string $defenderName Name of the defender who dodged.
     * @param string $message Message describing the dodge.
     *
     * @return self A CombatResult instance representing the dodge.
     */
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

    /**
     * Creates a CombatResult representing an exchange of attacks (e.g., counter-attack).
     *
     * @param int $playerDamageDealt Damage dealt by the player in the exchange.
     * @param int $monsterDamageDealt Damage dealt by the monster in the exchange.
     * @param string $message Descriptive message summarizing the exchange.
     * @param Health $playerHealth Player's health after the exchange.
     * @param Health $monsterHealth Monster's health after the exchange.
     *
     * @return self A CombatResult instance representing the exchange.
     */
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

    /**
     * Creates a CombatResult representing an error or invalid combat action.
     *
     * @param string $message Error message describing the issue.
     *
     * @return self A CombatResult instance representing the error.
     */
    public static function error(string $message): self
    {
        return new self(
            successful: false,
            message: $message
        );
    }

    /**
     * Indicates whether the combat action was successful.
     *
     * @return bool True if successful, false otherwise.
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Retrieves the descriptive message associated with the combat result.
     *
     * @return string The message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Gets the amount of damage dealt in this combat action.
     *
     * @return int Damage dealt.
     */
    public function getDamage(): int
    {
        return $this->damage;
    }

    /**
     * Returns experience points gained from this combat result.
     *
     * @return int Experience gained.
     */
    public function getExperienceGained(): int
    {
        return $this->experienceGained;
    }

    /**
     * Checks if the combat result represents a victory.
     *
     * @return bool True if victory, false otherwise.
     */
    public function isVictory(): bool
    {
        return $this->victory;
    }

    /**
     * Checks if the combat result represents a defeat.
     *
     * @return bool True if defeat, false otherwise.
     */
    public function isDefeat(): bool
    {
        return $this->defeat;
    }

    /**
     * Checks if the combat action was dodged.
     *
     * @return bool True if dodged, false otherwise.
     */
    public function isDodged(): bool
    {
        return $this->dodged;
    }

    /**
     * Calculates the defender's remaining health as a percentage of max health.
     *
     * Useful for UI health bars or other health-based logic.
     *
     * @return float Defender's health percentage (0-100).
     */
    public function getDefenderHealthPercentage(): float
    {
        if ($this->defenderMaxHealth === 0) {
            return 0;
        }

        return ($this->defenderHealthRemaining / $this->defenderMaxHealth) * 100;
    }
}
