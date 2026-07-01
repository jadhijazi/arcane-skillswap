<?php
/**
 * ============================================================================
 * bootstrap.php  —  Application Bootstrap
 * ============================================================================
 *
 * This file is the single entry-point that:
 *   1. Loads environment variables from .env
 *   2. Auto-loads every class file (no Composer needed for teammates)
 *   3. Instantiates the shared Database singleton
 *   4. Instantiates all DAO objects ready to inject
 *   5. Instantiates shared utility objects (JWT, Security, Validator)
 *
 * Usage (in any Slim 4 route file or standalone script):
 *   require_once __DIR__ . '/bootstrap.php';
 *   // Then use $db, $userDAO, $bookingDAO, $jwt, etc. directly.
 *
 * Author: Muhammad Ibrahim Khan (Database & Security Lead)
 */

declare(strict_types=1);

// ─── 1. LOAD ENVIRONMENT VARIABLES ──────────────────────────────────────────
// Reads key=value pairs from .env into $_ENV.
// Place your .env file in the project root (same folder as this bootstrap).

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    // Fail loudly in development; in production the variables come from the
    // server environment directly (set by Jad's deployment config).
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
    }
    throw new RuntimeException(
        '.env file not found. Copy .env.example to .env and fill in your values.'
    );
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    // Skip comment lines
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $key   = trim($key);
    $value = trim($value);

    // Remove surrounding quotes if present  (e.g.  DB_PASSWORD="my secret")
    if (
        (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
        (str_starts_with($value, "'") && str_ends_with($value, "'"))
    ) {
        $value = substr($value, 1, -1);
    }

    $_ENV[$key] = $value;
    putenv("{$key}={$value}");      // also available via getenv()
}

// ─── 2. CLASS AUTO-LOADER ────────────────────────────────────────────────────
// Maps class name → file path. Add new classes here as the project grows.

$classMap = [
    // Infrastructure
    'Database'       => __DIR__ . '/Database.php',

    // Security & Utilities
    'JWT'            => __DIR__ . '/JWT.php',
    'Security'       => __DIR__ . '/Security.php',
    'Validator'      => __DIR__ . '/Validator.php',

    // Data Access Objects
    'UserDAO'        => __DIR__ . '/UserDAO.php',
    'UserSkillDAO'   => __DIR__ . '/UserSkillDAO.php',
    'BookingDAO'     => __DIR__ . '/BookingDAO.php',
    'ReviewDAO'      => __DIR__ . '/ReviewDAO.php',
    'MessageDAO'     => __DIR__ . '/MessageDAO.php',
];

spl_autoload_register(function (string $className) use ($classMap): void {
    if (isset($classMap[$className])) {
        require_once $classMap[$className];
    }
});

// ─── 3. SHARED DATABASE INSTANCE ─────────────────────────────────────────────
// Singleton — only one PDO connection is opened for the whole request.

$db = Database::getInstance();

// ─── 4. DAO INSTANCES ────────────────────────────────────────────────────────
// All DAOs share the same $db connection (dependency injection).

$userDAO      = new UserDAO($db);
$userSkillDAO = new UserSkillDAO($db);
$bookingDAO   = new BookingDAO($db);
$reviewDAO    = new ReviewDAO($db);
$messageDAO   = new MessageDAO($db);

// ─── 5. UTILITY INSTANCES ────────────────────────────────────────────────────

// JWT — secret MUST be at least 32 characters (enforced in constructor).
// Store a long random string in .env as JWT_SECRET.
$jwt = new JWT($_ENV['JWT_SECRET'] ?? '', 3600);   // tokens expire in 1 hour

// Security & Validator are purely static, but instantiated here for completeness
// so teammates can type-hint them if they prefer.
// Usage: Security::htmlEscape($val)  OR  $security->htmlEscape($val)
$security  = new Security();
$validator = new Validator();

// ─── 6. GLOBAL JSON RESPONSE HELPER ─────────────────────────────────────────
// Aqil (Backend Lead) will call this from Slim routes.
// Defined here so it is available everywhere after bootstrap is loaded.

if (!function_exists('jsonResponse')) {
    /**
     * Send a JSON response and terminate.
     * Slim 4 routes should use the PSR-7 $response object instead,
     * but this helper is handy for quick scripts and tests.
     *
     * @param mixed $data     Payload to encode
     * @param int   $status   HTTP status code (default 200)
     */
    function jsonResponse(mixed $data, int $status = 200): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo Security::jsonEscape($data);
        exit;
    }
}

// ─── 7. AUTH MIDDLEWARE HELPER ───────────────────────────────────────────────
// Extracts and verifies the Bearer token from the Authorization header.
// Slim middleware will call this; also useful in standalone test scripts.

if (!function_exists('requireAuth')) {
    /**
     * Verify the Bearer JWT in the current request.
     *
     * @return array  Decoded token claims (user_id, email, role, …)
     * @throws RuntimeException on missing or invalid token
     */
    function requireAuth(JWT $jwt): array {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            jsonResponse(['error' => 'Missing or malformed Authorization header.'], 401);
        }

        $token = substr($authHeader, 7);

        try {
            return $jwt->verify($token);
        } catch (RuntimeException $e) {
            jsonResponse(['error' => $e->getMessage()], 401);
        }
    }
}
