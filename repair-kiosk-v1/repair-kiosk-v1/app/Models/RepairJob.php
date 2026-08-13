<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class RepairJob
{
    public static function findOpenByEquipmentId(int $equipmentId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, job_number, public_token, status, dropped_off_at
             FROM repair_jobs WHERE active_equipment_lock = :eid LIMIT 1'
        );
        $stmt->execute(['eid' => $equipmentId]);
        return $stmt->fetch() ?: null;
    }

    public static function findByToken(string $token): ?array
    {
        return self::findWithEquipment('j.public_token = :val', $token);
    }

    public static function find(int $id): ?array
    {
        return self::findWithEquipment('j.id = :val', (string) $id);
    }

    private static function findWithEquipment(string $where, string $value): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT j.id, j.job_number, j.public_token, j.status, j.equipment_id,
                    j.borrower_id, j.walkin_name, j.fault_description,
                    j.dropped_off_at, j.ready_at, j.collected_at,
                    j.estimated_ready_date, j.updated_at,
                    e.name AS equipment_name, e.asset_tag
             FROM repair_jobs j
             JOIN equipment e ON e.id = j.equipment_id
             WHERE {$where}
             LIMIT 1"
        );
        $stmt->execute(['val' => $value]);
        return $stmt->fetch() ?: null;
    }

    /** Next atomic job number for the current year: RJ-2026-000001. */
    public static function nextJobNumber(): string
    {
        $pdo = Database::connection();
        $year = (int) date('Y');

        $pdo->prepare('INSERT INTO job_counters (year_key, last_number) VALUES (:y, 0)
                        ON DUPLICATE KEY UPDATE year_key = year_key')
            ->execute(['y' => $year]);

        $pdo->prepare('UPDATE job_counters
                        SET last_number = LAST_INSERT_ID(last_number + 1)
                        WHERE year_key = :y')
            ->execute(['y' => $year]);

        $seq = (int) $pdo->lastInsertId();
        return sprintf('RJ-%d-%06d', $year, $seq);
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO repair_jobs
                (job_number, public_token, equipment_id, borrower_id,
                 walkin_name, walkin_contact, fault_description, status,
                 estimated_ready_date)
             VALUES
                (:job_number, :public_token, :equipment_id, :borrower_id,
                 :walkin_name, :walkin_contact, :fault_description, :status,
                 :estimated_ready_date)'
        );
        $stmt->execute([
            'job_number'           => $data['job_number'],
            'public_token'         => $data['public_token'],
            'equipment_id'         => $data['equipment_id'],
            'borrower_id'          => $data['borrower_id'] ?? null,
            'walkin_name'          => $data['walkin_name'] ?? null,
            'walkin_contact'       => $data['walkin_contact'] ?? null,
            'fault_description'    => $data['fault_description'],
            'status'               => $data['status'],
            'estimated_ready_date' => $data['estimated_ready_date'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function updateStatus(int $id, string $status, array $timestampFields = []): void
    {
        $set = ['status = :status'];
        $params = ['status' => $status, 'id' => $id];

        foreach ($timestampFields as $field) {
            $set[] = "{$field} = NOW()";
        }

        $sql = 'UPDATE repair_jobs SET ' . implode(', ', $set) . ' WHERE id = :id';
        Database::connection()->prepare($sql)->execute($params);
    }

    public static function listActive(): array
    {
        return Database::connection()
            ->query('SELECT * FROM v_active_jobs ORDER BY dropped_off_at ASC')
            ->fetchAll();
    }

    public static function statusHistory(int $jobId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT to_status, source, note, is_public, created_at
             FROM job_status_history
             WHERE job_id = :id
             ORDER BY created_at ASC'
        );
        $stmt->execute(['id' => $jobId]);
        return $stmt->fetchAll();
    }
}
