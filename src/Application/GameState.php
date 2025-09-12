<?php
declare(strict_types=1);

namespace DungeonCrawler\Application;

/**
 * Enumeration representing the different states of the game engine.
 *
 * Used to track and manage the current game phase such as menu, playing, combat, or game over.
 */
enum GameState: string
{
    /** The main menu state where the player can start/load/quit the game */
    case MENU = 'menu';

    /** The state where the player is actively exploring the dungeon */
    case PLAYING = 'playing';

    /** The state representing combat encounters */
    case COMBAT = 'combat';

    /** The game over state, reached after victory or defeat */
    case GAME_OVER = 'game_over';
}
