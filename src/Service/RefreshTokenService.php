<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Stores refresh tokens in Symfony's cache (configure the cache pool to use
 * Redis or another persistent backend in production).
 *
 * Each refresh token is a 256-bit random hex string keyed in cache.
 * On validation, the token is looked up. On rotation, the old token is revoked
 * and a new one is issued. This enables single-use refresh tokens with revocation.
 */
final class RefreshTokenService
{
    /** 7 days in seconds */
    private const TTL = 604800;

    public function __construct(private readonly CacheInterface $cache) {}

    /**
     * Creates a new refresh token tied to the given account and stores it.
     * Returns the raw token string to be set as a cookie value.
     */
    public function create(string $email, string $role): string
    {
        $token   = bin2hex(random_bytes(32)); // 256 bits of entropy → 64 hex chars
        $payload = json_encode(['email' => $email, 'role' => $role], JSON_THROW_ON_ERROR);

        // CacheInterface::get() calls the callback only on cache miss.
        // Since the token is generated fresh each time, the key is always new.
        $this->cache->get('rt_' . $token, static function (ItemInterface $item) use ($payload): string {
            $item->expiresAfter(self::TTL);

            return $payload;
        });

        return $token;
    }

    /**
     * Returns ['email' => ..., 'role' => ...] if the token is valid and not expired.
     * Returns null if missing, expired, or tampered with.
     */
    public function validate(string $token): ?array
    {
        // Reject obviously malformed tokens before hitting cache
        if (!ctype_xdigit($token) || strlen($token) !== 64) {
            return null;
        }

        // Use a flag to distinguish "hit" (callback not called) from "miss" (callback called)
        $miss    = false;
        $payload = $this->cache->get(
            'rt_' . $token,
            static function (ItemInterface $item) use (&$miss): string {
                $miss = true;
                $item->expiresAfter(1); // Expire the miss sentinel immediately
                return '{}';
            },
        );

        if ($miss) {
            return null;
        }

        return json_decode($payload, true);
    }

    /**
     * Revokes a refresh token so it can no longer be used.
     */
    public function revoke(string $token): void
    {
        $this->cache->delete('rt_' . $token);
    }
}
