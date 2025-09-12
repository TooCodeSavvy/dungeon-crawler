#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use DungeonCrawler\Presentation\ConsoleGame;

try {
    $game = new ConsoleGame();
    $game->start();
} catch (\Exception $e) {
    echo "\033[31mFatal Error: " . $e->getMessage() . "\033[0m\n";
    exit(1);
}