<?php
declare(strict_types=1);

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private string $secret;
    private string $issuer;
    private string $audience;

    public function __construct(array $config)
    {
        $this->secret = $config['secret'];
        $this->issuer = $config['issuer'] ?? '';
        $this->audience = $config['audience'] ?? '';
    }

    public function issueAccessToken(array $payload, int $ttlSeconds): string
    {
        $now = time();
        $token = array_merge($payload, [
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlSeconds,
            'iss' => $this->issuer,
            'aud' => $this->audience,
        ]);

        return JWT::encode($token, $this->secret, 'HS256');
    }

    public function decode(string $jwt): object
    {
        return JWT::decode($jwt, new Key($this->secret, 'HS256'));
    }
}
