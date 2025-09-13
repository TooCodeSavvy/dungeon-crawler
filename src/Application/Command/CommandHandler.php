<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Service\CombatService;
use DungeonCrawler\Domain\Service\MovementService;

/**
 * Class CommandHandler
 *
 * Responsible for routing and executing game commands.
 * It maps specific command classes to their respective handlers,
 * validates command execution eligibility, and manages command results.
 */
class CommandHandler
{
    /**
     * @var array<class-string, callable(CommandInterface, Game): CommandResult>
     * Maps command class names to handler callbacks.
     */
    private array $handlers = [];

    /**
     * CommandHandler constructor.
     *
     * @param MovementService $movementService Service to handle player movement.
     * @param CombatService $combatService Service to handle combat interactions.
     */
    public function __construct(
        private readonly MovementService $movementService,
        private readonly CombatService $combatService
    ) {
        $this->registerHandlers();
    }

    /**
     * Registers command handlers for supported commands.
     *
     * Each handler is a callable that takes a specific command and the game state,
     * and returns a CommandResult.
     *
     * @return void
     */
    private function registerHandlers(): void
    {
        $this->handlers = [
            MoveCommand::class   => fn(MoveCommand $cmd, Game $game): CommandResult => $this->handleMove($cmd, $game),
            AttackCommand::class => fn(AttackCommand $cmd, Game $game): CommandResult => $this->handleAttack($cmd, $game),
            TakeCommand::class   => fn(TakeCommand $cmd, Game $game): CommandResult => $this->handleTake($cmd, $game),
        ];
    }

    /**
     * Handles the provided command by executing it if allowed.
     *
     * Checks if the command can be executed in the current game state.
     * If yes, executes the command and returns the result.
     * Otherwise, returns a failure CommandResult.
     *
     * @param CommandInterface $command The command to handle.
     * @param Game|null $game The current game state or null if no active game.
     * @return CommandResult Result of command execution.
     */
    public function handle(CommandInterface $command, ?Game $game): CommandResult
    {
        if ($command->canExecute($game)) {
            return $command->execute($game);
        }

        return new CommandResult(false, 'Command cannot be executed.');
    }

    /**
     * Handles the MoveCommand.
     *
     * Moves the player in the specified direction if possible.
     *
     * @param MoveCommand $command The move command.
     * @param Game $game The current game state.
     * @return CommandResult Result of the move command.
     */
    private function handleMove(MoveCommand $command, Game $game): CommandResult
    {
        $result = $this->movementService->move(
            $game->getPlayer(),
            $command->getDirection(),
            $game->getDungeon()
        );

        if ($result->isSuccessful()) {
            // Update player position in the game
            $game->movePlayer($result->getNewPosition());

            return CommandResult::success(
                "You move " . $command->getDirection()->value . ". " .
                $result->getLocationInfo()->getDescription()
            );
        }

        return CommandResult::failure($result->getReason());
    }

    /**
     * Handles the AttackCommand.
     *
     * Performs an attack on the monster in the current room if one exists.
     * Awards score points for defeating a monster.
     *
     * @param AttackCommand $command The attack command.
     * @param Game $game The current game state.
     * @return CommandResult Result of the attack command.
     */
    private function handleAttack(AttackCommand $command, Game $game): CommandResult
    {
        $room = $game->getCurrentRoom();

        if (!$room->hasMonster()) {
            return CommandResult::failure("There's nothing to attack here!");
        }

        $result = $this->combatService->performAttack(
            $game->getPlayer(),
            $room->getMonster()
        );

        $message = $this->formatCombatResult($result);

        // Check if the monster was defeated during the attack
        if (!$room->getMonster()->isAlive()) {
            $room->removeMonster();
            $game->addScore(100);
            $message .= "\nThe monster is defeated! (+100 points)";
        }

        return CommandResult::success($message);
    }

    /**
     * Handles the TakeCommand.
     *
     * Allows the player to take treasures from the current room.
     * Adds treasures to the player's inventory and updates the score accordingly.
     *
     * @param TakeCommand $command The take command.
     * @param Game $game The current game state.
     * @return CommandResult Result of the take command.
     */
    private function handleTake(TakeCommand $command, Game $game): CommandResult
    {
        $room = $game->getCurrentRoom();

        if (!$room->hasTreasure()) {
            return CommandResult::failure("There's no treasure here!");
        }

        $treasures = $room->takeTreasure($command->getItemName());
        $totalValue = 0;
        $items = [];

        foreach ($treasures as $treasure) {
            $game->getPlayer()->addToInventory($treasure);
            $totalValue += $treasure->getValue();
            $items[] = $treasure->getName();
        }

        $game->addScore($totalValue);

        return CommandResult::success(
            sprintf("You take: %s (+%d points)", implode(', ', $items), $totalValue)
        );
    }

    /**
     * Formats the combat result into a human-readable string message.
     *
     * @param mixed $result The combat result object.
     * @return string Formatted combat message.
     */
    private function formatCombatResult($result): string
    {
        // Format combat result into readable message
        return sprintf(
            "You attack for %d damage! Monster counter-attacks for %d damage!",
            $result->getDamageDealt(),
            $result->getDamageTaken()
        );
    }
}
