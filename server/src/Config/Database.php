<?php
declare(strict_types=1);

namespace App\Config;

use PDO;

class Database
{
    private PDO $pdo;

    public function __construct(array $settings)
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $settings['host'], $settings['port'], $settings['database']);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->pdo = new PDO($dsn, $settings['username'], $settings['password'], $options);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
