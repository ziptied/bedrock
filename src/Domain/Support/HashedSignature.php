<?php

namespace Ziptied\Bedrock\Domain\Support;

class HashedSignature
{
    public static function compute(string $secret, string $payload): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    public static function verify(?string $provided, string $secret, string $payload): bool
    {
        if ($provided === null || $provided === '') {
            return false;
        }

        $expected = self::compute($secret, $payload);

        return hash_equals($expected, $provided);
    }
}
