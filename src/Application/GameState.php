<?php
declare(strict_types=1);

namespace DungeonCrawler\Application;

enum GameState: string
{
    case MENU = 'menu';
    case PLAYING = 'playing';
    case COMBAT = 'combat';
    case GAME_OVER = 'game_over';
}