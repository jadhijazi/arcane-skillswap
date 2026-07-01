<?php
declare(strict_types=1);

// Changed to lowercase 'bootstrap' to match your actual directory name on disk
use App\bootstrap\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

\Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$app = AppFactory::createApp();
$app->run();