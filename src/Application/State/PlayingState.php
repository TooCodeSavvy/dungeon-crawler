<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\State;

use DungeonCrawler\Application\Command\CommandInterface;
use DungeonCrawler\Application\Command\EquipCommand;
use DungeonCrawler\Application\Command\MoveCommand;
use DungeonCrawler\Application\Command\AttackCommand;
use DungeonCrawler\Application\Command\TakeCommand;
use DungeonCrawler\Application\Command\SaveCommand;
use DungeonCrawler\Application\Command\QuitCommand;
use DungeonCrawler\Application\Command\HelpCommand;
use DungeonCrawler\Application\Command\MapCommand;
use DungeonCrawler\Application\Command\InventoryCommand;
use DungeonCrawler\Application\Command\UseCommand;
use DungeonCrawler\Application\GameEngine;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Domain\Entity\Treasure;
use DungeonCrawler\Domain\Entity\TreasureType;
use DungeonCrawler\Domain\Service\CombatService;
use DungeonCrawler\Domain\Service\MovementService;
use DungeonCrawler\Infrastructure\Console\ConsoleRenderer;
use DungeonCrawler\Infrastructure\Console\InputParser;

/**
 * Represents the state of the game when the player is actively exploring and interacting with the dungeon.
 *
 * Responsible for rendering the current game view, parsing player input into commands,
 * and managing state transitions based on game conditions (combat, game over, victory).
 */
class PlayingState implements GameStateInterface
{
    /**
     * @var MovementService Movement service for processing moves
     */
    private MovementService $movementService;

    /**
     * @var CombatService Combat service for handling attacks
     */
    private CombatService $combatService;

    /**
     * @param GameEngine $engine The main game engine managing the game loop and state transitions.
     * @param StateFactory $stateFactory Factory to create other game states for transitions.
     */
    public function __construct(
        private readonly GameEngine $engine,
        private readonly StateFactory $stateFactory
    ) {
        // Initialize services
        $this->movementService = new MovementService();
        $this->combatService = new CombatService();
    }

    /**
     * Render the game view for the current state, including status, room description, and possible actions.
     *
     * @param ConsoleRenderer $renderer Renderer for outputting game information to the console.
     * @param Game|null $game Current game instance, cannot be null in playing state.
     * @param string|null $actionResult Optional result from the last action to display.
     * @param bool $showMap Whether to show the dungeon map.
     *
     * @throws \RuntimeException If no game is loaded when rendering.
     */
    public function render(
        ConsoleRenderer $renderer,
        ?Game $game,
        ?string $actionResult = null,
        bool $showMap = true
    ): void
    {
        if ($game === null) {
            throw new \RuntimeException('No game in playing state');
        }

        $renderer->clear();
        $renderer->renderGameStatus($game);
        $renderer->renderRoom($game->getCurrentRoom());

        // If there's an action result, display it in a visually distinct section
        if ($actionResult !== null && trim($actionResult) !== '') {
            $renderer->renderActionResult($actionResult);
        }

        // Always show the map regardless of the showMap parameter
        $renderer->renderAutoMap($game);

        $renderer->renderAvailableActions($this->getAvailableActions($game));
    }

    /**
     * Parse player input string and convert it into a command object for execution.
     *
     * @param string $input Raw input string from player.
     * @param InputParser $parser Helper to parse input into structured data.
     * @return CommandInterface|null Command object or null if input is invalid or unrecognized.
     */
    public function parseInput(string $input, InputParser $parser): ?CommandInterface
    {
        // Direct check for 'quit' command for reliability
        if (strtolower(trim($input)) === 'quit') {
            return new QuitCommand();
        }

        // Check for specific save commands
        if (strtolower(trim($input)) === 'save') {
            return new SaveCommand(false); // Update existing save
        }

        if (strtolower(trim($input)) === 'save as' || strtolower(trim($input)) === 'saveas') {
            return new SaveCommand(true); // Create new save
        }

        $parsed = $parser->parse($input);

        // Make sure all command parameter keys exist
        $direction = $parsed['direction'] ?? '';
        $target = $parsed['target'] ?? null;
        $item = $parsed['item'] ?? '';
        $as = $parsed['as'] ?? false;

        return match ($parsed['command']) {
            'move', 'go' => new MoveCommand($direction, $this->movementService),
            'attack', 'fight' => new AttackCommand($target, $this->combatService),
            'take', 'get' => new TakeCommand($item === '' ? 'all' : $item),
            'use', 'consume' => new UseCommand($item),
            'equip', 'wield', 'wear' => new EquipCommand($item),
            'save' => new SaveCommand($as),
            'quit' => new QuitCommand(),
            'help' => new HelpCommand(),
            'map' => new MapCommand(),
            'inventory' => new InventoryCommand(),
            default => null
        };
    }

    /**
     * Checks the game and current conditions to determine if a state transition is needed.
     *
     * @param Game|null $game Current game instance.
     * @return GameStateInterface|null The next state if a transition is required; otherwise null.
     */
    public function checkTransition(?Game $game): ?GameStateInterface
    {
        if ($game === null) {
            return null;
        }

        // Transition to combat state if player encounters a monster and is not already in combat
        if ($game->getCurrentRoom()->hasMonster() && !$game->isInCombat()) {
            return $this->stateFactory->createCombatState($this->engine);
        }

        // Defeat: Player is dead
        if (!$game->getPlayer()->isAlive()) {
            return $this->stateFactory->createGameOverState($this->engine, false);
        }

        // Victory: Player reached the exit room
        if ($game->getCurrentRoom()->isExit()) {
            return $this->stateFactory->createGameOverState($this->engine, true);
        }

        return null;
    }

    /**
     * Returns an array of available player actions in the current room.
     *
     * @param Game $game The current game instance.
     * @return array<string> List of actions player can take.
     */
    private function getAvailableActions(Game $game): array
    {
        $actions = [
            'move <direction>',
            'map',
            'inventory',
            'save',
            'attack',
            'flee',
            'take',
            'quit',
            'help'
        ];

        if ($game->getCurrentRoom()->hasTreasure()) {
            $actions[] = 'take <item|all>';
        }

        // Check if player has any items
        if (!empty($game->getPlayer()->getInventory())) {
            $actions[] = 'use <item>';

            // Check if player has any weapons to equip
            $hasWeapons = false;
            foreach ($game->getPlayer()->getInventory() as $item) {
                if ($item instanceof Treasure && $item->getType() === TreasureType::WEAPON) {
                    $hasWeapons = true;
                    break;
                }
            }

            if ($hasWeapons) {
                $actions[] = 'equip <weapon>';
            }
        }

        return $actions;
    }

    /**
     * Called when entering this state, can be used for setup or effects.
     *
     * @param Game|null $game The current game instance.
     */
    public function onEnter(?Game $game): void
    {
        // Optional: play sound or show animation on entering playing state
    }

    /**
     * Called when exiting this state, can be used for cleanup.
     *
     * @param Game|null $game The current game instance.
     */
    public function onExit(?Game $game): void
    {
        // Optional: cleanup resources or save state if needed
    }
}
