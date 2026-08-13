<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Equipment
{
    public static function findByAssetTag(string $assetTag): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, asset_tag, name, category, brand, model, status
             FROM equipment
             WHERE asset_tag = :tag AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['tag' => $assetTag]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, asset_tag, name, category, brand, model, status
             FROM equipment WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE equipment SET status = :status WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, asset_tag, name, category, status FROM equipment WHERE is_active = 1 ORDER BY name')
            ->fetchAll();
    }
}
