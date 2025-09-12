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
 * damage application, and combat resolution. It ensures that game rules around
 * attacking, dodging, critical hits, experience gain, and combat rounds are
 * consistently enforced.
 */
class CombatService
{
    /**
     * Executes a player attack against a monster.
     *
     * Validates that both combatants are alive, calculates damage including a chance
     * for critical hits, applies damage to the monster, then evaluates the outcome.
     * If the monster is defeated, experience points are awarded. Otherwise, the monster
     * gets an opportunity to counter-attack.
     *
     * @param Player $player The attacking player
     * @param Monster $monster The defending monster
     *
     * @return CombatResult The result of the attack, including messages and state changes
     */
    public function playerAttack(Player $player, Monster $monster): CombatResult
    {
        // Prevent attacks if player is dead
        if (!$player->isAlive()) {
            return CombatResult::error('You cannot attack while dead!');
        }

        // Prevent attacks on already defeated monsters
        if (!$monster->isAlive()) {
            return CombatResult::error('The monster is already defeated!');
        }

        // Calculate base damage from player's attack stats
        $damage = $player->attack();

        // Determine if attack is a critical hit to increase damage
        $criticalHit = $this->isCriticalHit();
        if ($criticalHit) {
            $damage = (int)($damage * 1.5);
        }

        // Store monster's health before damage for message construction
        $monsterHealthBefore = $monster->getHealth()->getValue();

        // Apply damage to monster's health pool
        $monster->takeDamage($damage);

        // Monster's health after damage applied
        $monsterHealthAfter = $monster->getHealth()->getValue();

        // Construct a descriptive message for the attack outcome
        $attackMessage = $this->buildPlayerAttackMessage(
            $player->getName(),
            $monster->getName(),
            $damage,
            $criticalHit,
            $monsterHealthAfter,
            $monster->getHealth()->getMax()
        );

        // If monster is dead, grant player experience and return victory result
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

        // If monster survived, execute its counter-attack against player
        return $this->executeMonsterCounterAttack($player, $monster, $attackMessage);
    }

    /**
     * Executes a monster attack against the player.
     *
     * Validates both combatants' alive status. Calculates monster damage,
     * checks if player dodges the attack, applies damage to the player if not dodged,
     * then returns the result including victory or defeat if applicable.
     *
     * @param Monster $monster The attacking monster
     * @param Player $player The defending player
     *
     * @return CombatResult The result of the attack, including messages and state changes
     */
    public function monsterAttack(Monster $monster, Player $player): CombatResult
    {
        // Prevent attacks if monster is dead
        if (!$monster->isAlive()) {
            return CombatResult::error('The monster cannot attack!');
        }

        // Prevent attacks on already defeated players
        if (!$player->isAlive()) {
            return CombatResult::error('The player is already defeated!');
        }

        // Calculate damage dealt by monster
        $damage = $monster->attack();

        // Check if player successfully dodges (chance-based)
        $dodged = $this->playerDodges();
        if ($dodged) {
            return CombatResult::dodged(
                attackerName: $monster->getName(),
                defenderName: $player->getName(),
                message: sprintf('You dodge the %s\'s attack!', $monster->getName())
            );
        }

        // Store player's health before damage for messaging
        $playerHealthBefore = $player->getHealth()->getValue();

        // Apply damage to player's health pool
        $player->takeDamage($damage);

        // Player's health after damage applied
        $playerHealthAfter = $player->getHealth()->getValue();

        // Construct descriptive attack message with health status
        $attackMessage = $this->buildMonsterAttackMessage(
            $monster->getName(),
            $player->getName(),
            $damage,
            $playerHealthAfter,
            $player->getHealth()->getMax()
        );

        // If player is defeated, return defeat result
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

        // Otherwise, return standard hit result
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
     * Simulates a full combat round consisting of the player's attack and the monster's counter-attack if still alive.
     *
     * This method orchestrates the flow of a typical combat exchange in a turn-based game:
     * the player attacks first; if the monster survives, it attacks back.
     * The method aggregates all combat actions into a CombatRound object to summarize the round.
     *
     * @param Player $player The player initiating the round
     * @param Monster $monster The monster defending and possibly counter-attacking
     *
     * @return CombatRound The combined result of all actions in the combat round
     */
    public function executeRound(Player $player, Monster $monster): CombatRound
    {
        $actions = [];

        // Player attacks first, result saved
        $playerAttackResult = $this->playerAttack($player, $monster);
        $actions[] = $playerAttackResult;

        // If monster still alive and player still alive, monster retaliates
        if ($monster->isAlive() && $player->isAlive() && !$playerAttackResult->isVictory()) {
            $monsterAttackResult = $this->monsterAttack($monster, $player);
            $actions[] = $monsterAttackResult;
        }

        // Return summary of combat round, including updated health states and whether combat ended
        return new CombatRound(
            actions: $actions,
            playerHealth: $player->getHealth(),
            monsterHealth: $monster->getHealth(),
            combatEnded: !$player->isAlive() || !$monster->isAlive()
        );
    }

    /**
     * Calculates if the player can successfully flee from combat.
     *
     * The flee chance is influenced by both the player's and monster's current health percentages,
     * making fleeing easier if the player is hurt and harder if the monster is healthy.
     * A successful flee ends combat; a failure results in a monster's free attack as a penalty.
     *
     * @param Player $player The player attempting to flee
     * @param Monster $monster The monster being fled from
     *
     * @return FleeResult The outcome of the flee attempt, including possible punishment damage
     */
    public function attemptFlee(Player $player, Monster $monster): FleeResult
    {
        // Calculate health percentages to influence flee chance
        $playerHealthPercent = $player->getHealth()->getPercentage();
        $monsterHealthPercent = $monster->getHealth()->getPercentage();

        // Base flee chance, modified by relative health status
        $baseChance = 30; // Base 30% chance to flee
        $healthModifier = (int)((100 - $playerHealthPercent) / 2); // Higher if player hurt
        $monsterModifier = (int)($monsterHealthPercent / 4); // Lower if monster healthy

        // Combine to determine final flee chance, capped at 75%
        $fleeChance = min(75, $baseChance + $healthModifier - $monsterModifier);

        // Random roll to determine flee success
        if (rand(1, 100) <= $fleeChance) {
            return FleeResult::success(
                'You manage to escape from the ' . $monster->getName() . '!'
            );
        }

        // Flee failed: monster gets free attack as punishment
        $punishment = $this->monsterAttack($monster, $player);

        return FleeResult::failure(
            'You fail to escape! The ' . $monster->getName() . ' blocks your path!',
            $punishment
        );
    }

    /**
     * Provides a snapshot of current combat statistics for display or UI purposes.
     *
     * Collects key player and monster stats such as names, health, attack power,
     * and experience rewards, to give an overview of the combatants' status.
     *
     * @param Player $player The player involved in combat
     * @param Monster $monster The monster involved in combat
     *
     * @return CombatStats Statistics object summarizing combat details
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
     * Handles the monster's counter-attack after a player attack.
     *
     * This method accounts for the player's chance to dodge, applies damage if hit,
     * updates combat messages, and returns the appropriate CombatResult reflecting
     * the outcome of the counter-attack, including defeat if the player dies.
     *
     * @param Player $player The player defending against counter-attack
     * @param Monster $monster The monster performing the counter-attack
     * @param string $previousMessage The message generated from the player's attack
     *
     * @return CombatResult Result of the monster's counter-attack and updated combat status
     */
    private function executeMonsterCounterAttack(
        Player $player,
        Monster $monster,
        string $previousMessage
    ): CombatResult {
        // Monster deals damage based on its attack power
        $monsterDamage = $monster->attack();

        // Determine if player dodges the counter-attack
        $dodged = $this->playerDodges();
        if ($dodged) {
            $fullMessage = $previousMessage . "\n" .
                sprintf('The %s attacks back, but you dodge!', $monster->getName());

            // Return a hit result with zero damage indicating dodge success
            return CombatResult::hit(
                attackerName: $player->getName(),
                defenderName: $monster->getName(),
                damage: 0,
                message: $fullMessage,
                defenderHealthRemaining: $monster->getHealth()->getValue(),
                defenderMaxHealth: $monster->getHealth()->getMax()
            );
        }

        // Apply damage to player if not dodged
        $player->takeDamage($monsterDamage);

        // Compose combined message including both attacks
        $fullMessage = $previousMessage . "\n" .
            $this->buildMonsterAttackMessage(
                $monster->getName(),
                $player->getName(),
                $monsterDamage,
                $player->getHealth()->getValue(),
                $player->getHealth()->getMax()
            );

        // Check if player has been defeated by counter-attack
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

        // Return exchange result reflecting damage dealt in the counter-attack
        return CombatResult::exchange(
            playerDamageDealt: 0, // Player damage was already counted before
            monsterDamageDealt: $monsterDamage,
            message: $fullMessage,
            playerHealth: $player->getHealth(),
            monsterHealth: $monster->getHealth()
        );
    }

    /**
     * Determines if an attack is a critical hit based on a fixed chance.
     *
     * Currently set to a 10% chance to simulate rare powerful strikes,
     * which increase damage by 50%.
     *
     * @return bool True if critical hit occurs, false otherwise
     */
    private function isCriticalHit(): bool
    {
        return rand(1, 100) <= 10; // 10% chance
    }

    /**
     * Determines if the player successfully dodges an incoming attack.
     *
     * Has a fixed 15% chance, representing the player's agility or luck,
     * negating the damage from that attack.
     *
     * @return bool True if dodge is successful, false otherwise
     */
    private function playerDodges(): bool
    {
        return rand(1, 100) <= 15; // 15% chance
    }

    /**
     * Builds a descriptive message for player attacks, including critical hits and monster health status.
     *
     * This message is used to inform players about the effectiveness of their attacks,
     * highlighting critical hits and remaining monster health for immersive feedback.
     *
     * @param string $playerName Name of the attacking player
     * @param string $monsterName Name of the defending monster
     * @param int $damage Damage dealt by the player
     * @param bool $critical Whether the hit was critical
     * @param int $monsterHealthRemaining Monster's current health after attack
     * @param int $monsterMaxHealth Monster's maximum health
     *
     * @return string Formatted attack message
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
     * Builds a descriptive message for monster attacks, highlighting damage dealt and player's health status.
     *
     * If the player's health is critically low after the attack, the message warns the player,
     * otherwise it shows the current health for situational awareness.
     *
     * @param string $monsterName Name of the attacking monster
     * @param string $playerName Name of the defending player
     * @param int $damage Damage dealt by the monster
     * @param int $playerHealthRemaining Player's current health after attack
     * @param int $playerMaxHealth Player's maximum health
     *
     * @return string Formatted attack message
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
