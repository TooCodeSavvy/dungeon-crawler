<?php

declare(strict_types=1);

namespace DungeonCrawler\Infrastructure\Console;

/**
 * InputParser is responsible for parsing raw user input into structured commands.
 */
class InputParser
{
    /**
     * Parses a raw input string into a command name and arguments.
     *
     * Example:
     *   "move north" => ['command' => 'move', 'args' => ['north']]
     *   "attack goblin" => ['command' => 'attack', 'args' => ['goblin']]
     *
     * @param string $input Raw input from user
     * @return array{command: string, args: string[]}
     */
    public function parse(string $input): array
    {
        // Trim input and split by whitespace
        $parts = preg_split('/\s+/', trim($input));

        if (empty($parts) || $parts[0] === '') {
            // No input, return empty command and args
            return [
                'command' => '',
                'args' => [],
            ];
        }

        // The first word is the command name
        $command = strtolower(array_shift($parts));

        // Remaining parts are arguments
        $args = $parts;

        return [
            'command' => $command,
            'args' => $args,
        ];
    }

    /**
     * Reads a line of input from the user.
     *
     * @return string The raw input string entered by the user.
     */
    public function getInput(): string
    {
        echo "> "; // Optionally display a prompt symbol
        $input = fgets(STDIN);

        if ($input === false) {
            // Handle EOF or error - treat as empty input
            return '';
        }

        return trim($input);
    }
}
