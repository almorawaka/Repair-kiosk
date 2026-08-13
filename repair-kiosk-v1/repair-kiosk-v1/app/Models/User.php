<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, username, password_hash, full_name, role, is_active, locked_until
             FROM users WHERE username = :u LIMIT 1'
        );
        $stmt->execute(['u' => $username]);
        return $stmt->fetch() ?: null;
    }

    public static function registerSuccessfulLogin(int $id): void
    {
        Database::connection()->prepare(
            'UPDATE users SET last_login_at = NOW(), failed_attempts = 0, locked_until = NULL WHERE id = :id'
        )->execute(['id' => $id]);
    }

    public static function registerFailedLogin(int $id): void
    {
        // Lock the account for 15 minutes after 5 consecutive failures.
        // A staff panel guarding a physical asset register is worth this
        // much friction; it stops a stolen kiosk terminal from being
        // used to brute-force a technician's password overnight.
        Database::connection()->prepare(
            'UPDATE users
             SET failed_attempts = failed_attempts + 1,
                 locked_until = CASE
                     WHEN failed_attempts + 1 >= 5 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                     ELSE locked_until
                 END
             WHERE id = :id'
        )->execute(['id' => $id]);
    }
}
