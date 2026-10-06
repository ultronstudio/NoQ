<?php

namespace Tests\Unit\Support;

use App\Support\Queue\TrackingToken;
use Tests\TestCase;

class TrackingTokenTest extends TestCase
{
    public function test_generate_returns_raw_value_with_at_least_256_bits_of_entropy(): void
    {
        $token = TrackingToken::generate();

        // 64 hex characters = 32 bytes = 256 bits.
        $this->assertSame(64, strlen($token->raw));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token->raw);
    }

    public function test_hash_is_sha256_of_the_raw_token(): void
    {
        $token = TrackingToken::generate();

        $this->assertSame(hash('sha256', $token->raw), $token->hash);
        $this->assertSame(64, strlen($token->hash));
    }

    public function test_hash_is_deterministic_for_the_same_raw_token(): void
    {
        $token = TrackingToken::generate();

        $this->assertSame(TrackingToken::hash($token->raw), $token->hash);
    }

    public function test_two_generated_tokens_are_not_equal(): void
    {
        $first = TrackingToken::generate();
        $second = TrackingToken::generate();

        $this->assertNotSame($first->raw, $second->raw);
        $this->assertNotSame($first->hash, $second->hash);
    }
}
