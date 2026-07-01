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
        $this->secret   = $config['secret'];
        $this->issuer   = $config['issuer'] ?? '';
        $this->audience = $config['audience'] ?? '';
    }

    public function issueAccessToken(array $payload, int $ttlSeconds): string
    {
        $now   = time();
        $token = array_merge($payload, [
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlSeconds,
            'iss' => $this->issuer,
            'aud' => $this->audience,
        ]);

        return JWT::encode($token, $this->secret, 'HS256');
    }

    private function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    public function decode(string $jwt): object
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Wrong number of segments');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Verify signature manually
        $expectedSig = hash_hmac('sha256', "$headerB64.$payloadB64", $this->secret, true);
        $providedSig  = $this->base64UrlDecode($signatureB64);

        if (!hash_equals($expectedSig, $providedSig)) {
            throw new \RuntimeException('Signature verification failed');
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadB64));
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid payload JSON: ' . json_last_error_msg());
        }

        // Validate expiry
        $now = time();
        if (isset($payload->exp) && $payload->exp < ($now - 60)) {
            throw new \RuntimeException('Token has expired');
        }

        // Validate not-before
        if (isset($payload->nbf) && $payload->nbf > ($now + 60)) {
            throw new \RuntimeException('Token not yet valid');
        }

        return $payload;
    }
}