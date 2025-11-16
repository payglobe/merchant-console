<?php
declare(strict_types=1);

final class Db
{
    public static function fromConfig(array $config): \PDO
    {
        $dsn  = (string)($config['DB_DSN']  ?? '');
        $user = (string)($config['DB_USER'] ?? '');
        $pass = (string)($config['DB_PASS'] ?? '');

        if ($dsn === '') {
            throw new \RuntimeException('DB_DSN missing in config.php');
        }

        $pdo = new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    }
}

