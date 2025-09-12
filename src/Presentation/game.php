#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Entry point for the Dungeon Crawler console game.
 *
 * This script initializes the console game and starts the game loop.
 * Any uncaught exceptions will be caught here and displayed with an error message.
 */

require __DIR__ . '/../../vendor/autoload.php';

use DungeonCrawler\Presentation\ConsoleGame;

try {
    // Instantiate and start the console game
    $game = new ConsoleGame();
    $game->start();
} catch (\Exception $e) {
    // Display fatal error message in red color
    echo "\033[31mFatal Error: " . $e->getMessage() . "\033[0m\n";
    exit(1);
}
