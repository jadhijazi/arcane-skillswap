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

    /**
     * Decode base64url -> raw bytes, with no text sanitization.
     * Use this for anything that isn't guaranteed to be text (e.g. the
     * raw HMAC signature) — stripping "control-looking" bytes out of
     * binary data corrupts it.
     */
    private function base64UrlDecodeRaw(string $input): string
    {
        $base64 = strtr($input, '-_', '+/');
        $remainder = strlen($base64) % 4;
        if ($remainder) {
            $base64 .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            throw new \RuntimeException('Base64 decode failed');
        }
        return $decoded;
    }

    /**
     * Decode base64url -> text, with BOM/invisible-char sanitization.
     * Only safe to use on things that are supposed to be text, like the
     * JSON payload — never on the raw binary signature.
     */
    private function base64UrlDecode(string $input): string
    {
        $decoded = $this->base64UrlDecodeRaw($input);
        // Strip BOM and invisible chars
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\xEF\xBB\xBF]/', '', $decoded);
    }

    public function decode(string $jwt): object
    {
        // Strip any whitespace/newlines from token
        $jwt = trim($jwt);

        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Wrong number of segments');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Verify HMAC signature
        $expectedSig = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $this->secret, true);
        $providedSig  = $this->base64UrlDecodeRaw($signatureB64);

        if (!hash_equals($expectedSig, $providedSig)) {
            throw new \RuntimeException('Signature verification failed');
        }

        // Decode payload
        $payloadJson = $this->base64UrlDecode($payloadB64);
        $payload = json_decode($payloadJson, false, 512, JSON_THROW_ON_ERROR);

        // Validate expiry with 60s leeway
        $now = time();
        if (isset($payload->exp) && $payload->exp < ($now - 60)) {
            throw new \RuntimeException('Token has expired');
        }

        if (isset($payload->nbf) && $payload->nbf > ($now + 60)) {
            throw new \RuntimeException('Token not yet valid');
        }

        return $payload;
    }
}