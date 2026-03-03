<?php
declare(strict_types=1);

require_once __DIR__ . '/project.php';

function dbMysql(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $configPath = __DIR__ . '/../config/db.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('db_config_not_found');
    }

    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('db_config_invalid');
    }

    $env = defined('APP_ENV') ? (string)APP_ENV : 'local';
    $dbConfig = $config[$env] ?? null;
    if (!is_array($dbConfig)) {
        throw new RuntimeException('db_config_env_not_found');
    }

    $host = (string)($dbConfig['host'] ?? 'localhost');
    $port = (int)($dbConfig['port'] ?? 3306);
    $dbName = (string)($dbConfig['dbname'] ?? '');
    $user = (string)($dbConfig['user'] ?? '');
    $pass = (string)($dbConfig['pass'] ?? '');

    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
