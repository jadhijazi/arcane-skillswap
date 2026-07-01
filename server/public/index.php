<?php
declare(strict_types=1);

// Handle CORS at the PHP entry point — before Slim routing runs.
// This guarantees preflight OPTIONS requests always get the right headers
// regardless of middleware ordering or Railway's proxy behaviour.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

use App\Bootstrap\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

\Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$app = AppFactory::createApp();
$app->run();