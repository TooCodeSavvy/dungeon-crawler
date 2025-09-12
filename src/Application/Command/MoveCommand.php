<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Service\MovementService;
use DungeonCrawler\Domain\ValueObject\Direction;

/**
 * Command to move the player in a specified direction within the dungeon.
 */
final class MoveCommand implements CommandInterface
{
    /**
     * @param string $direction The direction to move (e.g., "north", "south").
     * @param MovementService $movementService Service to handle movement logic.
     */
    public function __construct(
        private readonly string $direction,
        private readonly MovementService $movementService
    ) {}

    /**
     * Executes the move command.
     *
     * Attempts to move the player in the given direction. On success,
     * increments the game turn and returns a success message with location info.
     * On failure, returns an appropriate failure message.
     *
     * @param Game $game The current game state.
     * @return CommandResult Result of the move attempt.
     */
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
            // The direction string was invalid (not recognized)
            return CommandResult::failure("Invalid direction: {$this->direction}");
        }
    }

    /**
     * Checks if the command can currently be executed.
     *
     * The player can move only if they are alive and not currently in combat.
     *
     * @param Game $game The current game state.
     * @return bool True if move is allowed, false otherwise.
     */
    public function canExecute(Game $game): bool
    {
        return !$game->isInCombat() && $game->getPlayer()->isAlive();
    }

    /**
     * Returns the name of this command.
     *
     * @return string Command name.
     */
    public function getName(): string
    {
        return 'move';
    }
}
