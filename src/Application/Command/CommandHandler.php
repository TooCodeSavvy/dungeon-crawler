<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Service\CombatService;
use DungeonCrawler\Domain\Service\MovementService;

final class CommandHandler
{
    private array $handlers = [];

    public function __construct(
        private readonly MovementService $movementService,
        private readonly CombatService $combatService
    ) {
        $this->registerHandlers();
    }

    private function registerHandlers(): void
    {
        $this->handlers = [
            MoveCommand::class => fn($cmd, $game) => $this->handleMove($cmd, $game),
            AttackCommand::class => fn($cmd, $game) => $this->handleAttack($cmd, $game),
            TakeCommand::class => fn($cmd, $game) => $this->handleTake($cmd, $game),
        ];
    }

    public function handle(CommandInterface $command, Game $game): CommandResult
    {
        $commandClass = get_class($command);

        if (!isset($this->handlers[$commandClass])) {
            return CommandResult::failure("Unknown command: " . $command->getName());
        }

        if (!$command->canExecute($game)) {
            return CommandResult::failure("Cannot execute " . $command->getName() . " right now.");
        }

        return $this->handlers[$commandClass]($command, $game);
    }

    private function handleMove(MoveCommand $command, Game $game): CommandResult
    {
        $result = $this->movementService->move(
            $game->getPlayer(),
            $command->getDirection(),
            $game->getDungeon()
        );

        if ($result->isSuccessful()) {
            $game->movePlayer($result->getNewPosition());
            return CommandResult::success(
                "You move " . $command->getDirection()->value . ". " .
                $result->getLocationInfo()->getDescription()
            );
        }

        return CommandResult::failure($result->getReason());
    }

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

        if (!$room->getMonster()->isAlive()) {
            $room->removeMonster();
            $game->addScore(100);
            $message .= "\nThe monster is defeated! (+100 points)";
        }

        return CommandResult::success($message);
    }

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