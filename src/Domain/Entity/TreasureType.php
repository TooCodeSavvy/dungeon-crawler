<?php

declare(strict_types=1);

namespace DungeonCrawler\Domain\Entity;

/**
 * Enum TreasureType
 *
 * Represents the types of treasure that can be found in the dungeon.
 * Each type can provide a display name, icon, and terminal color code.
 */
enum TreasureType: string
{
    case GOLD = 'gold';
    case HEALTH_POTION = 'health_potion';
    case WEAPON = 'weapon';
    case ARTIFACT = 'artifact';

    /**
     * Returns a human-readable display name for the treasure type.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::GOLD => 'Gold',
            self::HEALTH_POTION => 'Health Potion',
            self::WEAPON => 'Weapon',
            self::ARTIFACT => 'Artifact',
        };
    }

    /**
     * Returns an emoji icon representing the treasure type.
     *
     * @return string
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::GOLD => '💰',
            self::HEALTH_POTION => '🧪',
            self::WEAPON => '⚔️',
            self::ARTIFACT => '🏺',
        };
    }

    /**
     * Returns an ANSI terminal color code for the treasure type.
     *
     * Useful for CLI-based UIs.
     *
     * @return string
     */
    public function getColor(): string
    {
        return match ($this) {
            self::GOLD => "\033[33m",           // Yellow
            self::HEALTH_POTION => "\033[31m",  // Red
            self::WEAPON => "\033[36m",         // Cyan
            self::ARTIFACT => "\033[35m",       // Magenta
        };
    }
}
