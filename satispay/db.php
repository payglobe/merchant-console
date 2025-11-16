<?php
// =========================================
// 2) db.php (MySQLi)
// =========================================

namespace App; 

class Db
{
    private static ?\mysqli $conn = null;

    public static function conn(): \mysqli
    {
        if (self::$conn instanceof \mysqli) {
            return self::$conn;
        }
        $m = new \mysqli(Config::DB_HOST, Config::DB_USER, Config::DB_PASS, Config::DB_NAME);
        if ($m->connect_errno) {
            throw new \RuntimeException('DB connection failed: ' . $m->connect_error);
        }
        // utf8mb4
        $m->set_charset('utf8mb4');
        self::$conn = $m;
        return $m;
    }
}
?>