<?php

declare(strict_types=1);

namespace DungeonCrawler\Infrastructure\Console;

/**
 * InputParser is responsible for parsing raw user input into structured commands.
 */
class InputParser
{
    /**
     * Parses a raw input string into structured command data.
     *
     * @param string $input The raw input string from the user
     * @return array<string, string|bool> Structured command data
     */
    public function parse(string $input): array
    {
        // Trim and convert to lowercase
        $input = trim(strtolower($input));

        // Handle "save as" command
        if ($input === 'save as' || $input === 'saveas') {
            return [
                'command' => 'save',
                'as' => true
            ];
        }

        // Split the input into words
        $words = preg_split('/\s+/', $input);

        // Empty input
        if (empty($words) || empty($words[0])) {
            return ['command' => ''];
        }

        // Extract the command (first word)
        $command = $words[0];

        // Extract all arguments (everything after the command)
        $args = count($words) > 1 ? implode(' ', array_slice($words, 1)) : '';

        // Handle special cases for movement commands
        if (in_array($command, ['n', 's', 'e', 'w', 'north', 'south', 'east', 'west'])) {
            return [
                'command' => 'move',
                'direction' => $command
            ];
        }

        // Command-specific parsing
        return match($command) {
            'move', 'go' => [
                'command' => 'move',
                'direction' => $args
            ],
            'take', 'get' => [
                'command' => 'take',
                'item' => $args
            ],
            'attack', 'fight' => [
                'command' => 'attack',
                'target' => $args
            ],
            'use', 'consume' => [
                'command' => 'use',
                'item' => $args
            ],
            'equip', 'wield', 'wear' => [
                'command' => 'equip',
                'item' => $args
            ],
            // Simple commands
            'quit', 'exit', 'q' => ['command' => 'quit'],
            'help', 'h', '?' => ['command' => 'help'],
            'map', 'm' => ['command' => 'map'],
            'inventory', 'inv', 'i' => ['command' => 'inventory'],
            'save' => ['command' => 'save'],
            // Default case
            default => ['command' => $command]
        };
    }

}
