<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

use DungeonCrawler\Application\State\GameStateInterface;

/**
 * Represents the outcome of executing a command.
 */
class CommandResult
{
    /**
     * @param bool $success Whether the command succeeded.
     * @param string $message A human-readable message about the result.
     * @param array $data Optional additional data related to the result.
     */
    function __construct(
        private readonly bool $success,
        private readonly string $message,
        private readonly array $data = [],
        private readonly bool $requiresStateChange = false,
        private readonly ?GameStateInterface $newState = null
    ) {}

    /**
     * Checks if this result requires a state change in the game.
     *
     * @return bool True if a state change is required.
     */
    public function requiresStateChange(): bool
    {
        return $this->requiresStateChange;
    }


    /**
     * Gets the new state to transition to, if applicable.
     *
     * @return GameStateInterface|null The new state or null if no state change is required.
     */
    public function getNewState(): ?GameStateInterface
    {
        return $this->newState;
    }

    /**
     * Creates a result that requires a state transition.
     *
     * @param GameStateInterface $newState The state to transition to.
     * @param string $message Optional message to display.
     * @param array $data Optional additional data.
     * @return self
     */
    public static function stateTransition(
        GameStateInterface $newState,
        string             $message = '',
        array              $data = []
    ): self
    {
        return new self(true, $message, $data, true, $newState);
    }

    /**
     * Creates a successful command result.
     *
     * @param string $message Success message.
     * @param array $data Optional additional data.
     * @return self
     */
    public static function success(string $message, array $data = []): self
    {
        return new self(true, $message, $data);
    }

    /**
     * Creates a failed command result.
     *
     * @param string $message Failure message.
     * @param array $data Optional additional data.
     * @return self
     */
    public static function failure(string $message, array $data = []): self
    {
        return new self(false, $message, $data);
    }

    /**
     * Indicates if the command execution was successful.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Indicates if the command execution failed.
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Gets the message associated with this result.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Gets additional data related to this result.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Checks if the additional data contains a given key.
     */
    public function hasData(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Retrieves a value from additional data or returns a default if not found.
     *
     * @param string $key
     * @param mixed $default Default value if key not found.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Checks if this result has a non-empty message.
     *
     * @return bool True if there's a message, false otherwise.
     */
    public function hasMessage(): bool
    {
        return !empty($this->message);
    }
}
