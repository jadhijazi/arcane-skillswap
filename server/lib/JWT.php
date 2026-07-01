<?php
/**
 * ============================================================================
 * JWT (JSON Web Token) Utility
 * ============================================================================
 * 
 * Handles JWT token generation, verification, and claims extraction.
 * Uses HS256 (HMAC SHA-256) algorithm for signing.
 * 
 * Author: Muhammad Ibrahim Khan (Database & Security Lead)
 * 
 * Usage:
 *   $jwt = new JWT($_ENV['JWT_SECRET']);
 *   $token = $jwt->issue(['user_id' => 1, 'email' => 'user@utm.edu.my'], 3600);
 *   $claims = $jwt->verify($token);
 */

class JWT {
    private string $secret;
    private int $defaultExpiresIn;
    private const ALGORITHM = 'HS256';
    
    public function __construct(string $secret, int $defaultExpiresIn = 3600) {
        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT secret must be at least 32 characters long');
        }
        $this->secret = $secret;
        $this->defaultExpiresIn = $defaultExpiresIn;
    }
    
    /**
     * Issue a new JWT token
     * 
     * @param array $claims Token claims (user_id, email, role, etc.)
     * @param int $expiresIn Expiration time in seconds (default: 1 hour)
     * @return string JWT token
     */
    public function issue(array $claims, int $expiresIn = null): string {
        $expiresIn = $expiresIn ?? $this->defaultExpiresIn;
        
        // Add standard claims
        $claims['iat'] = time();
        $claims['exp'] = time() + $expiresIn;
        
        // Encode header, payload, and signature
        $header = $this->base64UrlEncode(json_encode([
            'alg' => self::ALGORITHM,
            'typ' => 'JWT'
        ]));
        
        $payload = $this->base64UrlEncode(json_encode($claims));
        
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $this->secret, true)
        );
        
        return "{$header}.{$payload}.{$signature}";
    }
    
    /**
     * Verify and decode a JWT token
     * 
     * @param string $token JWT token to verify
     * @return array Token claims if valid
     * @throws RuntimeException If token is invalid or expired
     */
    public function verify(string $token): array {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token format');
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Verify signature
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $this->secret, true)
        );
        
        if (!hash_equals($signatureEncoded, $expectedSignature)) {
            throw new RuntimeException('Invalid token signature');
        }
        
        // Decode payload
        $claims = json_decode(
            $this->base64UrlDecode($payloadEncoded),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        
        // Check expiration
        if (isset($claims['exp']) && $claims['exp'] < time()) {
            throw new RuntimeException('Token has expired');
        }
        
        return $claims;
    }
    
    /**
     * Extract claims from token without verification (use carefully!)
     * Useful for logging or debugging
     */
    public function decode(string $token): array {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token format');
        }
        
        return json_decode(
            $this->base64UrlDecode($parts[1]),
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }
    
    /**
     * Refresh a token (issue new token with same claims but new expiration)
     */
    public function refresh(string $token, int $expiresIn = null): string {
        $claims = $this->verify($token);
        
        // Remove old expiration and issued-at claims
        unset($claims['exp'], $claims['iat']);
        
        return $this->issue($claims, $expiresIn);
    }
    
    /**
     * Check if token is expired
     */
    public function isExpired(string $token): bool {
        try {
            $this->verify($token);
            return false;
        } catch (RuntimeException $e) {
            return str_contains($e->getMessage(), 'expired');
        }
    }
    
    /**
     * Get claim value safely
     */
    public function getClaim(string $token, string $claim): mixed {
        try {
            $claims = $this->verify($token);
            return $claims[$claim] ?? null;
        } catch (RuntimeException) {
            return null;
        }
    }
    
    /**
     * Base64 URL encode (JWT standard)
     */
    private function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode (JWT standard)
     */
    private function base64UrlDecode(string $data): string {
        $padding = 4 - (strlen($data) % 4);
        if ($padding !== 4) {
            $data .= str_repeat('=', $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}