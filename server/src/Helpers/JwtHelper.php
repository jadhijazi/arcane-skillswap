<?php
declare(strict_types=1);

namespace App\Helpers;

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

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

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

    private function base64UrlDecode(string $input): string
    {
        $decoded = $this->base64UrlDecodeRaw($input);
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\xEF\xBB\xBF]/', '', $decoded);
    }

    public function issueAccessToken(array $payload, int $ttlSeconds): string
    {
        $now = time();
        $payload = array_merge($payload, [
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlSeconds,
            'iss' => $this->issuer,
            'aud' => $this->audience,
        ]);

        $header    = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $body      = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', "{$header}.{$body}", $this->secret, true));

        return "{$header}.{$body}.{$signature}";
    }

    public function decode(string $jwt): object
    {
        $jwt = trim($jwt);
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Wrong number of segments');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Verify HMAC signature
        $expectedSig = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $this->secret, true);
        $providedSig = $this->base64UrlDecodeRaw($signatureB64);

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