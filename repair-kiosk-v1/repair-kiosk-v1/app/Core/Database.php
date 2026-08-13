<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $config = require BASE_PATH . '/app/Config/database.php';
            self::$instance = new PDO(
                $config['dsn'],
                $config['username'],
                $config['password'],
                $config['options']
            );
        }
        return self::$instance;
    }
}
