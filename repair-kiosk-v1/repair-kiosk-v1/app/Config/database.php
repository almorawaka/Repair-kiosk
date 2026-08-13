<?php
declare(strict_types=1);

/**
 * app/Config/database.php
 * ---------------------------------------------------------------------
 * Connection settings for the single MySQL/MariaDB connection this app
 * uses. app/Core/Database.php reads this array to build a PDO instance
 * and should be the ONLY place in the codebase that calls `new PDO(...)`.
 *
 * Everything here is read from .env — see .env.example for the full
 * list of keys with local-dev defaults.
 * ---------------------------------------------------------------------
 */

return [

    'driver'   => 'mysql',
    'host'     => env('DB_HOST', '127.0.0.1'),
    'port'     => env('DB_PORT', '3306'),
    'database' => env('DB_NAME', 'repair_kiosk'),
    'username' => env('DB_USER', 'root'),
    'password' => env('DB_PASS', ''),
    'charset'  => env('DB_CHARSET', 'utf8mb4'),
    'collation' => 'utf8mb4_unicode_ci',

    // Built here rather than in Database.php so the DSN format is
    // config, not code. Change once if you ever support pgsql/sqlite.
    'dsn' => sprintf(
        '%s:host=%s;port=%s;dbname=%s;charset=%s',
        'mysql',
        env('DB_HOST', '127.0.0.1'),
        env('DB_PORT', '3306'),
        env('DB_NAME', 'repair_kiosk'),
        env('DB_CHARSET', 'utf8mb4')
    ),

    // Passed as the 4th arg to `new PDO()`. Exceptions-on-error is
    // non-negotiable for this app — silent DB failures at a kiosk are
    // how you get equipment stuck in a broken state with no error
    // ever reaching anyone.
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4', time_zone = '+05:30'",
    ],
];
