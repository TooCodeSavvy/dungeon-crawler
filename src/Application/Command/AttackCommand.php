<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Entity\Player;
use DungeonCrawler\Domain\Entity\Monster;
use DungeonCrawler\Domain\Entity\Treasure;
use DungeonCrawler\Domain\Factory\TreasureFactory;

/**
 * Class AttackCommand
 *
 * Represents a command to attack a monster in the current room.
 * Handles attack logic including damage calculation, monster counter-attacks,
 * scoring, and potential treasure drops.
 */
class AttackCommand implements CommandInterface
{
    /**
     * @var string|null Name of the target to attack, optional.
     */
    private ?string $target;

    /**
     * Constructor.
     *
     * @param string|null $target Optional name of the target monster.
     */
    public function __construct(?string $target = null)
    {
        $this->target = $target;
    }

    /**
     * Executes the attack command.
     *
     * @param ?Game $game The current game state.
     * @return CommandResult Result of the attack action.
     */
    public function execute(?Game $game): CommandResult
    {
        if ($game === null) {
            return CommandResult::failure("Game is not initialized.");
        }

        // Determine the monster to attack - either in the room or blocking the path
        $monster = null;
        $isBlockingMonster = false;

        if ($game->getCurrentRoom()->hasMonster()) {
            $monster = $game->getCurrentRoom()->getMonster();
        } elseif ($game->isPathBlocked()) {
            $monster = $game->getBlockingMonster();
            $isBlockingMonster = true;
        }

        if ($monster === null) {
            return CommandResult::failure("There's nothing to attack here!");
        }

        $player = $game->getPlayer();

        // Validate the target if specified and ensure it matches the monster
        if ($this->target !== null && !$this->isValidTarget($this->target, $monster->getName())) {
            return CommandResult::failure(
                sprintf(
                    "Cannot attack '%s'. The %s is your only target.",
                    $this->target,
                    $monster->getName()
                )
            );
        }

        // Calculate and apply player's attack damage
        $playerDamage = $this->calculateDamage($player->getAttackPower());
        $monster->takeDamage($playerDamage);

        $messages = [];
        $messages[] = sprintf(
            "⚔️ You strike the %s for %d damage!",
            $monster->getName(),
            $playerDamage
        );

        // If monster is defeated, handle victory logic
        if (!$monster->isAlive()) {
            // Handle monster removal based on whether it's a blocking monster or in the current room
            if ($isBlockingMonster) {
                // Get the direction and the room in that direction
                $direction = $game->getBlockedDirection();
                $newPosition = $game->getCurrentPosition()->move($direction);
                $targetRoom = $game->getDungeon()->getRoomAt($newPosition);

                // Remove the monster from the target room
                if ($targetRoom !== null && $targetRoom->hasMonster()) {
                    $targetRoom->removeMonster();
                }

                // Clear the blocking state
                $game->clearBlockingMonster();
            } else {
                // Remove monster from current room
                $game->getCurrentRoom()->removeMonster();
            }

            $game->endCombat();

            // Calculate and award score points
            $points = $this->calculatePoints($monster);
            $game->addScore($points);

            $messages[] = sprintf(
                "💀 The %s has been defeated! You gain %d points!",
                $monster->getName(),
                $points
            );

            // Possibly drop treasure in the current room
            if ($this->shouldDropTreasure()) {
                $treasure = $this->generateTreasureDrop($monster);
                $game->getCurrentRoom()->addTreasure($treasure);

                $messages[] = sprintf(
                    "✨ The %s dropped %s!",
                    $monster->getName(),
                    $treasure->getName()
                );
            }

            if ($isBlockingMonster) {
                $messages[] = "The path is now clear!";
            }

            return CommandResult::success(implode("\n", $messages));
        }

        // Monster counter-attacks if still alive
        $monsterDamage = $this->calculateDamage($monster->getAttackPower());
        $player->takeDamage($monsterDamage);

        $messages[] = sprintf(
            "🔥 The %s strikes back for %d damage!",
            $monster->getName(),
            $monsterDamage
        );

        // Append health status bars for both player and monster
        $messages[] = $this->getHealthStatus($player, $monster);

        // If player died, return failure result
        if (!$player->isAlive()) {
            $messages[] = "💔 You have been defeated...";
            return CommandResult::failure(implode("\n", $messages));
        }

        // Return success with the combat messages
        return CommandResult::success(implode("\n", $messages));
    }

    /**
     * Checks whether this command can be executed in the current game state.
     *
     * @param ?Game $game The current game state.
     * @return bool True if the player is alive and there is a monster to attack.
     */
    public function canExecute(?Game $game): bool
    {
        if ($game === null) {
            return false;
        }

        $player = $game->getPlayer();
        $room = $game->getCurrentRoom();

        // Check for monster in current room OR a blocking monster
        $hasMonsterToAttack = $room->hasMonster() || $game->isPathBlocked();

        if ($room->hasMonster()) {
            echo "- Room monster name: " . $room->getMonster()->getName() . "\n";
        }

        if ($game->isPathBlocked()) {
            echo "- Blocking monster name: " . $game->getBlockingMonster()->getName() . "\n";
        }

        return $player->isAlive() && $hasMonsterToAttack;
    }

    /**
     * Returns the command name.
     *
     * @return string The name of this command.
     */
    public function getName(): string
    {
        return 'attack';
    }

    /**
     * Validates if the given target name matches the monster's name.
     *
     * Allows partial matching and generic names such as "monster" or "enemy".
     *
     * @param string $target The target name input.
     * @param string $monsterName The monster's actual name.
     * @return bool True if the target is valid.
     */
    private function isValidTarget(string $target, string $monsterName): bool
    {
        $normalizedTarget = strtolower(trim($target));
        $normalizedMonster = strtolower($monsterName);

        return str_contains($normalizedMonster, $normalizedTarget) ||
            $normalizedTarget === 'monster' ||
            $normalizedTarget === 'enemy';
    }

    /**
     * Calculates damage dealt given a base attack power.
     *
     * Damage varies between 80% and 120% of base, with a 10% chance of critical hit (x1.5).
     *
     * @param int $baseDamage The base attack power.
     * @return int The calculated damage (minimum 1).
     */
    private function calculateDamage(int $baseDamage): int
    {
        $variance = (int)($baseDamage * 0.2);
        $damage = rand($baseDamage - $variance, $baseDamage + $variance);

        // 10% chance for critical hit
        if (rand(1, 100) <= 10) {
            $damage = (int)($damage * 1.5);
        }

        return max(1, $damage);
    }

    /**
     * Calculates points awarded for defeating a monster.
     *
     * Points are based on monster's max health and attack power.
     *
     * @param Monster $monster The defeated monster.
     * @return int The score points awarded.
     */
    private function calculatePoints(Monster $monster): int
    {
        $basePoints = $monster->getHealth()->getMax() * 2;
        $bonusPoints = $monster->getAttackPower() * 3;

        return $basePoints + $bonusPoints;
    }

    /**
     * Determines whether treasure should be dropped.
     *
     * 30% chance to drop treasure.
     *
     * @return bool True if treasure drops.
     */
    private function shouldDropTreasure(): bool
    {
        return rand(1, 100) <= 30;
    }

    /**
     * Generates a treasure drop based on monster difficulty.
     *
     * Stronger monsters drop rarer treasure.
     *
     * @param Monster $monster The defeated monster.
     * @return Treasure The generated treasure.
     */
    private function generateTreasureDrop(Monster $monster): Treasure
    {
        $factory = new TreasureFactory();

        $rarity = match (true) {
            $monster->getHealth()->getMax() >= 100 => 'rare',
            $monster->getHealth()->getMax() >= 50  => 'uncommon',
            default                                => 'common',
        };

        return $factory->createRandom($rarity);
    }

    /**
     * Creates a formatted health status string for player and monster.
     *
     * @param Player $player The player entity.
     * @param Monster $monster The monster entity.
     * @return string The formatted health status message.
     */
    private function getHealthStatus(Player $player, Monster $monster): string
    {
        $playerHealth = $player->getHealth();
        $monsterHealth = $monster->getHealth();

        $playerBar = $this->createHealthBar(
            $playerHealth->getValue(),
            $playerHealth->getMax()
        );

        $monsterBar = $this->createHealthBar(
            $monsterHealth->getValue(),
            $monsterHealth->getMax()
        );

        return sprintf(
            "Your HP: %s %d/%d | %s HP: %s %d/%d",
            $playerBar,
            $playerHealth->getValue(),
            $playerHealth->getMax(),
            $monster->getName(),
            $monsterBar,
            $monsterHealth->getValue(),
            $monsterHealth->getMax()
        );
    }

    /**
     * Creates a visual health bar string using block characters.
     *
     * @param int $current Current health points.
     * @param int $max Maximum health points.
     * @return string Visual health bar.
     */
    private function createHealthBar(int $current, int $max): string
    {
        $percentage = ($current / $max) * 100;
        $bars = 10;
        $filled = (int)(($percentage / 100) * $bars);

        return '[' . str_repeat('█', $filled) . str_repeat('░', $bars - $filled) . ']';
    }

    /**
     * Returns the attack target name if specified.
     *
     * @return string|null The target name or null if none specified.
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }
}
