<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use DungeonCrawler\Domain\Entity\TreasureType;

/**
 * @covers \DungeonCrawler\Domain\Entity\TreasureType
 *
 * Unit tests for the TreasureType enum.
 *
 * This test suite verifies:
 * - Display names of each treasure type.
 * - Icon representations.
 * - ANSI terminal color codes.
 * - Underlying string values of the enum cases.
 */
final class TreasureTypeTest extends TestCase
{
    /**
     * Ensures each treasure type returns the correct human-readable display name.
     */
    public function test_display_names(): void
    {
        $this->assertSame('Gold', TreasureType::GOLD->getDisplayName());
        $this->assertSame('Health Potion', TreasureType::HEALTH_POTION->getDisplayName());
        $this->assertSame('Weapon', TreasureType::WEAPON->getDisplayName());
        $this->assertSame('Artifact', TreasureType::ARTIFACT->getDisplayName());
    }

    /**
     * Ensures each treasure type provides the correct emoji/icon representation.
     */
    public function test_icons(): void
    {
        $this->assertSame('💰', TreasureType::GOLD->getIcon());
        $this->assertSame('🧪', TreasureType::HEALTH_POTION->getIcon());
        $this->assertSame('⚔️', TreasureType::WEAPON->getIcon());
        $this->assertSame('🏺', TreasureType::ARTIFACT->getIcon());
    }

    /**
     * Ensures each treasure type provides the correct ANSI color code
     * (used in terminal/CLI displays).
     */
    public function test_terminal_colors(): void
    {
        $this->assertSame("\033[33m", TreasureType::GOLD->getColor());          // Yellow
        $this->assertSame("\033[31m", TreasureType::HEALTH_POTION->getColor()); // Red
        $this->assertSame("\033[36m", TreasureType::WEAPON->getColor());        // Cyan
        $this->assertSame("\033[35m", TreasureType::ARTIFACT->getColor());      // Magenta
    }

    /**
     * Ensures enum values match their string representations as defined.
     */
    public function test_enum_values(): void
    {
        $this->assertSame('gold', TreasureType::GOLD->value);
        $this->assertSame('health_potion', TreasureType::HEALTH_POTION->value);
        $this->assertSame('weapon', TreasureType::WEAPON->value);
        $this->assertSame('artifact', TreasureType::ARTIFACT->value);
    }
}
