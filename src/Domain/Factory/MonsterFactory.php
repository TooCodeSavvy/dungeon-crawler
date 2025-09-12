<?php

declare(strict_types=1);

namespace DungeonCrawler\Domain\Factory;

use DungeonCrawler\Domain\Entity\Monster;
use DungeonCrawler\Domain\ValueObject\Health;

/**
 * Factory class responsible for creating predefined monster instances.
 *
 * This implementation centralizes the construction logic for all core monster types.
 * By encapsulating this logic, the game engine or dungeon generator can remain
 * agnostic of specific monster details, improving separation of concerns.
 */
final class MonsterFactory implements MonsterFactoryInterface
{
    /**
     * Create a Goblin monster with fixed stats.
     *
     * @return Monster A Goblin with 30 HP, 10 attack power, and 15 XP reward.
     */
    public function createGoblin(): Monster
    {
        return new Monster(
            name: 'Goblin',
            health: Health::full(30),
            attackPower: 10,
            experienceReward: 15
        );
    }

    /**
     * Create an Orc monster with fixed stats.
     *
     * @return Monster An Orc with 50 HP, 15 attack power, and 25 XP reward.
     */
    public function createOrc(): Monster
    {
        return new Monster(
            name: 'Orc',
            health: Health::full(50),
            attackPower: 15,
            experienceReward: 25
        );
    }

    /**
     * Create a Dragon monster with fixed stats.
     *
     * @return Monster A Dragon with 100 HP, 30 attack power, and 100 XP reward.
     */
    public function createDragon(): Monster
    {
        return new Monster(
            name: 'Dragon',
            health: Health::full(100),
            attackPower: 30,
            experienceReward: 100
        );
    }
}
