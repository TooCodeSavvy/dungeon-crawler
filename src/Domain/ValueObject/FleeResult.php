<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\Service;

/**
 * Value object representing the result of a flee attempt.
 */
final class FleeResult
{
    private function __construct(
        private readonly bool $successful,
        private readonly string $message,
        private readonly ?CombatResult $punishment = null
    ) {}

    public static function success(string $message): self
    {
        return new self(
            successful: true,
            message: $message
        );
    }

    public static function failure(string $message, CombatResult $punishment): self
    {
        return new self(
            successful: false,
            message: $message,
            punishment: $punishment
        );
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getMessage(): string
    {
        if ($this->punishment !== null) {
            return $this->message . "\n" . $this->punishment->getMessage();
        }

        return $this->message;
    }

    public function getPunishment(): ?CombatResult
    {
        return $this->punishment;
    }
}