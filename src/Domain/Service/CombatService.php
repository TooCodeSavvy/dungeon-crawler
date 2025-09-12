<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Service;

use DungeonCrawler\Domain\Entity\Monster;
use DungeonCrawler\Domain\Entity\Player;
use DungeonCrawler\Domain\ValueObject\CombatResult;

/**
 * Service responsible for handling combat mechanics between players and monsters.
 *
 * This service encapsulates all combat logic including attack calculations,
 * damage application, and combat resolution.
 */
class CombatService
{
    /**
     * Executes a player attack against a monster.
     *
     * @param Player $player The attacking player
     * @param Monster $monster The defending monster
     *
     * @return CombatResult The result of the attack
     */
    public function playerAttack(Player $player, Monster $monster): CombatResult
    {
        if (!$player->isAlive()) {
            return CombatResult::error('You cannot attack while dead!');
        }

        if (!$monster->isAlive()) {
            return CombatResult::error('The monster is already defeated!');
        }

        // Calculate player damage
        $damage = $player->attack();
        $criticalHit = $this->isCriticalHit();

        if ($criticalHit) {
            $damage = (int)($damage * 1.5);
        }

        // Apply damage to monster
        $monsterHealthBefore = $monster->getHealth()->getValue();
        $monster->takeDamage($damage);
        $monsterHealthAfter = $monster->getHealth()->getValue();

        // Build attack message
        $attackMessage = $this->buildPlayerAttackMessage(
            $player->getName(),
            $monster->getName(),
            $damage,
            $criticalHit,
            $monsterHealthAfter,
            $monster->getHealth()->getMax()
        );

        // Check if monster was defeated
        if (!$monster->isAlive()) {
            $player->gainExperience($monster->getExperienceReward());

            return CombatResult::victory(
                attackerName: $player->getName(),
                defenderName: $monster->getName(),
                damage: $damage,
                message: $attackMessage,
                experienceGained: $monster->getExperienceReward(),
                defenderHealthRemaining: 0,
                defenderMaxHealth: $monster->getHealth()->getMax()
            );
        }

        // Monster counter-attacks if still alive
        return $this->executeMonsterCounterAttack($player, $monster, $attackMessage);
    }

    /**
     * Executes a monster attack against the player.
     *
     * @param Monster $monster The attacking monster
     * @param Player $player The defending player
     *
     * @return CombatResult The result of the attack
     */
    public function monsterAttack(Monster $monster, Player $player): CombatResult
    {
        if (!$monster->isAlive()) {
            return CombatResult::error('The monster cannot attack!');
        }

        if (!$player->isAlive()) {
            return CombatResult::error('The player is already defeated!');
        }

        // Calculate monster damage
        $damage = $monster->attack();
        $dodged = $this->playerDodges();

        if ($dodged) {
            return CombatResult::dodged(
                attackerName: $monster->getName(),
                defenderName: $player->getName(),
                message: sprintf('You dodge the %s\'s attack!', $monster->getName())
            );
        }

        // Apply damage to player
        $playerHealthBefore = $player->getHealth()->getValue();
        $player->takeDamage($damage);
        $playerHealthAfter = $player->getHealth()->getValue();

        // Build attack message
        $attackMessage = $this->buildMonsterAttackMessage(
            $monster->getName(),
            $player->getName(),
            $damage,
            $playerHealthAfter,
            $player->getHealth()->getMax()
        );

        // Check if player was defeated
        if (!$player->isAlive()) {
            return CombatResult::defeat(
                attackerName: $monster->getName(),
                defenderName: $player->getName(),
                damage: $damage,
                message: $attackMessage,
                defenderHealthRemaining: 0,
                defenderMaxHealth: $player->getHealth()->getMax()
            );
        }

        return CombatResult::hit(
            attackerName: $monster->getName(),
            defenderName: $player->getName(),
            damage: $damage,
            message: $attackMessage,
            defenderHealthRemaining: $playerHealthAfter,
            defenderMaxHealth: $player->getHealth()->getMax()
        );
    }

    /**
     * Simulates a full combat round (player attacks, monster counter-attacks).
     *
     * @param Player $player The player
     * @param Monster $monster The monster
     *
     * @return CombatRound The complete round result
     */
    public function executeRound(Player $player, Monster $monster): CombatRound
    {
        $actions = [];

        // Player attacks first
        $playerAttackResult = $this->playerAttack($player, $monster);
        $actions[] = $playerAttackResult;

        // If monster survived and player is still alive, monster attacks
        if ($monster->isAlive() && $player->isAlive() && !$playerAttackResult->isVictory()) {
            $monsterAttackResult = $this->monsterAttack($monster, $player);
            $actions[] = $monsterAttackResult;
        }

        return new CombatRound(
            actions: $actions,
            playerHealth: $player->getHealth(),
            monsterHealth: $monster->getHealth(),
            combatEnded: !$player->isAlive() || !$monster->isAlive()
        );
    }

    /**
     * Calculates if the player can flee from combat.
     *
     * @param Player $player The player attempting to flee
     * @param Monster $monster The monster being fled from
     *
     * @return FleeResult The result of the flee attempt
     */
    public function attemptFlee(Player $player, Monster $monster): FleeResult
    {
        // Base flee chance depends on health percentage
        $playerHealthPercent = $player->getHealth()->getPercentage();
        $monsterHealthPercent = $monster->getHealth()->getPercentage();

        // Higher chance to flee if player is hurt or monster is healthy
        $baseChance = 30;
        $healthModifier = (int)((100 - $playerHealthPercent) / 2);
        $monsterModifier = (int)($monsterHealthPercent / 4);

        $fleeChance = min(75, $baseChance + $healthModifier - $monsterModifier);

        if (rand(1, 100) <= $fleeChance) {
            return FleeResult::success(
                'You manage to escape from the ' . $monster->getName() . '!'
            );
        }

        // Failed flee attempt - monster gets a free attack
        $punishment = $this->monsterAttack($monster, $player);

        return FleeResult::failure(
            'You fail to escape! The ' . $monster->getName() . ' blocks your path!',
            $punishment
        );
    }

    /**
     * Gets combat statistics for display.
     *
     * @param Player $player The player
     * @param Monster $monster The monster
     *
     * @return CombatStats Statistics about the current combat
     */
    public function getCombatStats(Player $player, Monster $monster): CombatStats
    {
        return new CombatStats(
            playerName: $player->getName(),
            playerHealth: $player->getHealth(),
            playerAttackPower: $player->getAttackPower(),
            monsterName: $monster->getName(),
            monsterHealth: $monster->getHealth(),
            monsterAttackPower: $monster->getAttackPower(),
            monsterExperienceReward: $monster->getExperienceReward()
        );
    }

    /**
     * Executes monster counter-attack after player attack.
     */
    private function executeMonsterCounterAttack(
        Player $player,
        Monster $monster,
        string $previousMessage
    ): CombatResult {
        $monsterDamage = $monster->attack();
        $dodged = $this->playerDodges();

        if ($dodged) {
            $fullMessage = $previousMessage . "\n" .
                sprintf('The %s attacks back, but you dodge!', $monster->getName());

            return CombatResult::hit(
                attackerName: $player->getName(),
                defenderName: $monster->getName(),
                damage: 0,
                message: $fullMessage,
                defenderHealthRemaining: $monster->getHealth()->getValue(),
                defenderMaxHealth: $monster->getHealth()->getMax()
            );
        }

        $player->takeDamage($monsterDamage);

        $fullMessage = $previousMessage . "\n" .
            $this->buildMonsterAttackMessage(
                $monster->getName(),
                $player->getName(),
                $monsterDamage,
                $player->getHealth()->getValue(),
                $player->getHealth()->getMax()
            );

        if (!$player->isAlive()) {
            return CombatResult::defeat(
                attackerName: $monster->getName(),
                defenderName: $player->getName(),
                damage: $monsterDamage,
                message: $fullMessage,
                defenderHealthRemaining: 0,
                defenderMaxHealth: $player->getHealth()->getMax()
            );
        }

        return CombatResult::exchange(
            playerDamageDealt: 0, // Already counted in original attack
            monsterDamageDealt: $monsterDamage,
            message: $fullMessage,
            playerHealth: $player->getHealth(),
            monsterHealth: $monster->getHealth()
        );
    }

    /**
     * Determines if an attack is a critical hit.
     */
    private function isCriticalHit(): bool
    {
        return rand(1, 100) <= 10; // 10% chance
    }

    /**
     * Determines if the player dodges an attack.
     */
    private function playerDodges(): bool
    {
        return rand(1, 100) <= 15; // 15% chance
    }

    /**
     * Builds a descriptive message for player attacks.
     */
    private function buildPlayerAttackMessage(
        string $playerName,
        string $monsterName,
        int $damage,
        bool $critical,
        int $monsterHealthRemaining,
        int $monsterMaxHealth
    ): string {
        $attackVerb = $critical ? 'critically strike' : 'attack';
        $message = sprintf(
            'You %s the %s for %d damage!',
            $attackVerb,
            $monsterName,
            $damage
        );

        if ($critical) {
            $message .= ' 💥 CRITICAL HIT!';
        }

        if ($monsterHealthRemaining <= 0) {
            $message .= sprintf(' The %s has been defeated!', $monsterName);
        } else {
            $message .= sprintf(' (%d/%d HP remaining)', $monsterHealthRemaining, $monsterMaxHealth);
        }

        return $message;
    }

    /**
     * Builds a descriptive message for monster attacks.
     */
    private function buildMonsterAttackMessage(
        string $monsterName,
        string $playerName,
        int $damage,
        int $playerHealthRemaining,
        int $playerMaxHealth
    ): string {
        $message = sprintf(
            'The %s attacks you for %d damage!',
            $monsterName,
            $damage
        );

        if ($playerHealthRemaining <= 0) {
            $message .= ' You have been defeated!';
        } else {
            $healthPercent = ($playerHealthRemaining / $playerMaxHealth) * 100;

            if ($healthPercent <= 25) {
                $message .= sprintf(' ⚠️ Critical health! (%d/%d HP)', $playerHealthRemaining, $playerMaxHealth);
            } else {
                $message .= sprintf(' (Your health: %d/%d HP)', $playerHealthRemaining, $playerMaxHealth);
            }
        }

        return $message;
    }
}