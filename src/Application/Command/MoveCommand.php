<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Service\MovementService;
use DungeonCrawler\Domain\ValueObject\Direction;

final class MoveCommand implements CommandInterface
{
    public function __construct(
        private readonly string $direction,
        private readonly MovementService $movementService
    ) {}

    public function execute(Game $game): CommandResult
    {
        try {
            $direction = Direction::fromString($this->direction);
            $result = $this->movementService->move(
                $game->getPlayer(),
                $direction,
                $game->getDungeon()
            );

            if ($result->isSuccessful()) {
                $game->incrementTurn();
                $message = sprintf(
                    "You move %s. %s",
                    $direction->value,
                    $result->getLocationInfo()->getDescription()
                );

                return CommandResult::success($message);
            }

            return CommandResult::failure($result->getReason());

        } catch (\InvalidArgumentException $e) {
            return CommandResult::failure("Invalid direction: {$this->direction}");
        }
    }

    public function canExecute(Game $game): bool
    {
        return !$game->isInCombat() && $game->getPlayer()->isAlive();
    }

    public function getName(): string
    {
        return 'move';
    }
}