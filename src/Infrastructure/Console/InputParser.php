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
     * @return array<string, string> Structured command data
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

        // Handle special cases for movement commands
        if (in_array($command, ['n', 's', 'e', 'w', 'north', 'south', 'east', 'west'])) {
            return [
                'command' => 'move',
                'direction' => $command
            ];
        }

        // For 'move' or 'go' commands, the second word is the direction
        if (($command === 'move' || $command === 'go') && isset($words[1])) {
            return [
                'command' => $command,
                'direction' => $words[1]
            ];
        }

        // For 'take' or 'get' commands, the second word is the item
        if (($command === 'take' || $command === 'get') && isset($words[1])) {
            return [
                'command' => $command,
                'item' => $words[1]
            ];
        }

        // For 'attack' or 'fight' commands, the rest is the target
        if ($command === 'attack' || $command === 'fight') {
            $target = count($words) > 1 ? implode(' ', array_slice($words, 1)) : null;
            return [
                'command' => $command,
                'target' => $target
            ];
        }

        // For simple commands without parameters
        return ['command' => $command];
    }

    /**
     * Gets a line of input from the user.
     *
     * @param string $prompt Optional prompt to display
     * @return string The user's input
     */
    public function getInput(string $prompt = '> '): string
    {
        echo $prompt;
        $input = fgets(STDIN);

        if ($input === false) {
            // Handle EOF or error
            return '';
        }

        return $input;
    }
}
