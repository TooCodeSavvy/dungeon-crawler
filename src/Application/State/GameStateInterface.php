<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\State;

use DungeonCrawler\Application\Command\CommandInterface;
use DungeonCrawler\Domain\Entity\Game;
use DungeonCrawler\Infrastructure\Console\ConsoleRenderer;
use DungeonCrawler\Infrastructure\Console\InputParser;

interface GameStateInterface
{
    public function render(ConsoleRenderer $renderer, ?Game $game): void;

    public function parseInput(string $input, InputParser $parser): ?CommandInterface;

    public function checkTransition(?Game $game): ?GameStateInterface;

    public function onEnter(?Game $game): void;

    public function onExit(?Game $game): void;
}