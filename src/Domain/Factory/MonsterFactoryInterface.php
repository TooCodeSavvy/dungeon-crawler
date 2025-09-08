<?php

declare(strict_types=1);

namespace DungeonCrawler\Domain\Factory;

use DungeonCrawler\Domain\Entity\Monster;

/**
 * Contract for a factory responsible for creating predefined Monster instances.
 *
 * This interface enforces consistent creation of common monster types across the game.
 * It supports the Dependency Inversion Principle (DIP), allowing for different
 * factory implementations to be injected where needed (e.g., for testing, dynamic scaling, etc.).
 */
interface MonsterFactoryInterface
{
    /**
     * Create a Goblin monster.
     *
     * Goblins are typically weaker enemies with lower health and damage.
     *
     * @return Monster A new Goblin instance.
     */
    public function createGoblin(): Monster;

    /**
     * Create an Orc monster.
     *
     * Orcs are mid-tier enemies with moderate health and attack power.
     *
     * @return Monster A new Orc instance.
     */
    public function createOrc(): Monster;

    /**
     * Create a Dragon monster.
     *
     * Dragons are boss-tier enemies with high health, damage, and reward.
     *
     * @return Monster A new Dragon instance.
     */
    public function createDragon(): Monster;
}
