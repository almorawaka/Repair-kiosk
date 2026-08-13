<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Borrower
{
    public static function findByCode(string $code): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, borrower_code, full_name, email, phone, department, borrower_type
             FROM borrowers
             WHERE borrower_code = :code AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['code' => $code]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, borrower_code, full_name, email, phone, department
             FROM borrowers WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
