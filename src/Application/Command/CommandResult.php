<?php
declare(strict_types=1);

namespace DungeonCrawler\Application\Command;

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
        private readonly array $data = []
    ) {}

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
}
