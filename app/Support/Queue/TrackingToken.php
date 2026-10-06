<?php

namespace App\Support\Queue;

/**
 * A cryptographically random token used to let an anonymous visitor look up
 * their own QueueEntry later, without being a Laravel User.
 *
 * Only the SHA-256 hash is ever persisted (queue_entries.tracking_token_hash).
 * The raw value is returned to the client once, at creation time, and must
 * never be stored in the database.
 */
final readonly class TrackingToken
{
    private function __construct(
        public string $raw,
        public string $hash,
    ) {}

    /**
     * Generate a new token with 256 bits of entropy from the OS CSPRNG.
     */
    public static function generate(): self
    {
        $raw = bin2hex(random_bytes(32));

        return new self($raw, self::hash($raw));
    }

    /**
     * Hash a raw token the same way as at generation time, for lookups.
     */
    public static function hash(string $raw): string
    {
        return hash('sha256', $raw);
    }
}
