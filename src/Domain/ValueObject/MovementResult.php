<?php
declare(strict_types=1);

namespace DungeonCrawler\Domain\ValueObject;

use DungeonCrawler\Domain\Entity\Monster;

/**
 * Value object representing the result of a movement attempt.
 *
 * Contains information about whether the movement was successful,
 * any blocking entities, and information about the new location.
 */
class MovementResult
{
    /**
     * @param bool $successful Whether the movement was successful
     * @param string|null $reason Reason for failure if not successful
     * @param Monster|null $blockingEntity Entity blocking the movement, if any
     * @param LocationInfo|null $locationInfo Information about the new location if successful
     */
    private function __construct(
        private readonly bool $successful,
        private readonly ?string $reason = null,
        private readonly ?Monster $blockingEntity = null,
        private readonly ?LocationInfo $locationInfo = null
    ) {}

    /**
     * Creates a successful movement result.
     *
     * @param LocationInfo $locationInfo Information about the new location
     * @return self
     */
    public static function success(LocationInfo $locationInfo): self
    {
        return new self(true, null, null, $locationInfo);
    }

    /**
     * Creates a failed movement result with a reason.
     *
     * @param string $reason The reason why movement failed
     * @return self
     */
    public static function failure(string $reason): self
    {
        return new self(false, $reason);
    }

    /**
     * Creates a movement result blocked by an entity.
     *
     * @param string $reason The reason for being blocked
     * @param Monster $blockingEntity The entity blocking the way
     * @return self
     */
    public static function blocked(string $reason, Monster $blockingEntity): self
    {
        return new self(false, $reason, $blockingEntity);
    }

    /**
     * Checks if the movement was successful.
     *
     * @return bool True if successful, false otherwise
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Gets the reason for movement failure.
     *
     * @return string|null The reason, or null if successful
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Gets information about the new location.
     *
     * @return LocationInfo|null Location info, or null if movement failed
     */
    public function getLocationInfo(): ?LocationInfo
    {
        return $this->locationInfo;
    }
}