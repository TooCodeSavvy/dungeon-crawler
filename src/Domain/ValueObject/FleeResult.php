<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\ValueObject;

/**
 * Value object representing the outcome of a flee attempt during combat.
 *
 * Encapsulates whether the flee was successful, an associated message for feedback,
 * and optionally any punishment (usually a counterattack) if the flee attempt fails.
 */
class FleeResult
{
    /**
     * Private constructor to enforce use of factory methods.
     *
     * @param bool $successful Indicates if the flee attempt succeeded
     * @param string $message Descriptive message about the flee attempt result
     * @param CombatResult|null $punishment Optional combat result applied as punishment on failure
     */
    private function __construct(
        private readonly bool $successful,
        private readonly string $message,
        private readonly ?CombatResult $punishment = null
    ) {}

    /**
     * Creates a successful flee result.
     *
     * @param string $message Message to convey successful escape
     *
     * @return self Instance representing a successful flee
     */
    public static function success(string $message): self
    {
        return new self(
            successful: true,
            message: $message
        );
    }

    /**
     * Creates a failed flee result, including the punishment applied.
     *
     * Typically the punishment is a monster counterattack triggered by failed escape.
     *
     * @param string $message Message explaining failure
     * @param CombatResult $punishment Combat result detailing the punishment inflicted
     *
     * @return self Instance representing a failed flee with punishment
     */
    public static function failure(string $message, CombatResult $punishment): self
    {
        return new self(
            successful: false,
            message: $message,
            punishment: $punishment
        );
    }

    /**
     * Checks if the flee attempt was successful.
     *
     * @return bool True if flee succeeded, false otherwise
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Gets the full message describing the flee attempt result.
     *
     * If punishment exists (on failure), appends the punishment's message for detailed feedback.
     *
     * @return string Full descriptive message
     */
    public function getMessage(): string
    {
        if ($this->punishment !== null) {
            return $this->message . "\n" . $this->punishment->getMessage();
        }

        return $this->message;
    }

    /**
     * Gets the punishment combat result if flee failed.
     *
     * This represents the counterattack or other consequence imposed due to failed flee.
     *
     * @return CombatResult|null Punishment combat result, or null if flee succeeded
     */
    public function getPunishment(): ?CombatResult
    {
        return $this->punishment;
    }
}
