<?php
/**
 * ============================================================================
 * Security Utility - XSS Prevention & Output Escaping
 * ============================================================================
 * 
 * Prevents XSS (Cross-Site Scripting) attacks by properly escaping output
 * based on context (HTML, JavaScript, URL, CSS).
 * 
 * Rule: Always escape output at the point of rendering, never in the database.
 * 
 * Author: Muhammad Ibrahim Khan (Database & Security Lead)
 * 
 * Usage:
 *   echo Security::htmlEscape($user['name']);
 *   $url = Security::urlEscape($url);
 *   $json = Security::jsonEscape($data);
 */

class Security {
    
    /**
     * Escape output for HTML context
     * Prevents XSS by converting HTML special characters to entities
     * 
     * Use when: Outputting user data inside HTML (tags, attributes, text)
     */
    public static function htmlEscape(mixed $data): string {
        if (is_array($data)) {
            return array_map([self::class, 'htmlEscape'], $data);
        }
        
        return htmlspecialchars(
            (string) $data,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
    
    /**
     * Escape for HTML attribute context (more strict)
     * 
     * Use when: Outputting user data in HTML attributes (data-*, title, etc.)
     */
    public static function attributeEscape(string $data): string {
        return htmlspecialchars(
            $data,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
    
    /**
     * Escape for JavaScript context
     * Escapes special characters that could break out of JS strings
     * 
     * Use when: Passing PHP data to JavaScript (json_encode preferred)
     */
    public static function jsEscape(string $data): string {
        return addcslashes($data, "\\\n\r\t\"'");
    }
    
    /**
     * Escape for URL context
     * Properly encodes URL parameters
     * 
     * Use when: Building URLs with query parameters
     */
    public static function urlEscape(string $url): string {
        return rawurlencode($url);
    }
    
    /**
     * Escape for CSS context
     * Escapes special characters in CSS values
     * 
     * Use when: Dynamically building CSS strings
     */
    public static function cssEscape(string $value): string {
        return preg_replace('/[^a-zA-Z0-9_\-#()]/', '', $value);
    }
    
    /**
     * Safe JSON encoding (already safe, but explicit)
     * 
     * Use when: Returning JSON data from API
     */
    public static function jsonEscape(mixed $data): string {
        return json_encode(
            $data,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );
    }
    
    /**
     * Strip HTML/PHP tags (dangerous - use htmlEscape instead)
     * Only use when you want to remove all markup
     */
    public static function stripTags(string $data): string {
        return strip_tags($data);
    }
    
    /**
     * Sanitize email to prevent header injection
     */
    public static function sanitizeEmail(string $email): string {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Generate CSRF token for forms
     * 
     * Usage:
     *   $_SESSION['csrf_token'] = Security::generateCsrfToken();
     *   // In form: <input name="csrf_token" value="<?= htmlEscape($_SESSION['csrf_token']) ?>">
     */
    public static function generateCsrfToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token, string $sessionToken): bool {
        return hash_equals($token, $sessionToken);
    }
    
    /**
     * Hash password using bcrypt (PHP 7.2+)
     * 
     * Usage:
     *   $hash = Security::hashPassword($password);
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password against hash
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing (cost increased, etc.)
     */
    public static function passwordNeedsRehash(string $hash): bool {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Generate secure random token (for password reset, email verification)
     */
    public static function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Rate limiting helper - check if user exceeded limit
     * Returns true if limited, false if allowed
     * 
     * Usage:
     *   if (Security::isRateLimited($userIp, 'login', 5, 900)) {
     *       throw new Exception('Too many login attempts');
     *   }
     */
    public static function isRateLimited(string $identifier, string $action, int $maxAttempts, int $windowSeconds): bool {
        // This is a simplified version - in production, use Redis or memcached
        $cacheKey = "ratelimit:{$action}:{$identifier}";
        
        // For demonstration: using file-based cache (in /tmp)
        $cacheFile = "/tmp/{$cacheKey}";
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            
            // Check if window has expired
            if (time() - $data['first'] > $windowSeconds) {
                // Window expired, reset
                unlink($cacheFile);
                return false;
            }
            
            // Check if exceeded limit
            if ($data['count'] >= $maxAttempts) {
                return true;
            }
            
            // Increment counter
            $data['count']++;
            file_put_contents($cacheFile, json_encode($data));
            return false;
        }
        
        // First attempt in window
        file_put_contents($cacheFile, json_encode(['count' => 1, 'first' => time()]));
        return false;
    }
    
    /**
     * Sanitize file upload filename
     * Prevents path traversal and unsafe characters
     */
    public static function sanitizeFilename(string $filename): string {
        // Remove path components
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $filename);
        
        // Ensure it doesn't start with dot
        $filename = ltrim($filename, '.');
        
        return $filename;
    }
    
    /**
     * Validate file upload (size, type, MIME)
     */
    public static function validateFileUpload(
        array $file,
        array $allowedMimes = ['image/jpeg', 'image/png'],
        int $maxSizeBytes = 5242880
    ): array {
        $errors = [];
        
        // Check file size
        if ($file['size'] > $maxSizeBytes) {
            $errors[] = 'File size exceeds maximum limit';
        }
        
        // Check MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimes, true)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedMimes);
        }
        
        // Check for file upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error: ' . self::getUploadErrorMessage($file['error']);
        }
        
        return $errors;
    }
    
    /**
     * Get human-readable file upload error message
     */
    private static function getUploadErrorMessage(int $errorCode): string {
        return match($errorCode) {
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary directory',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            default => 'Unknown upload error'
        };
    }
}