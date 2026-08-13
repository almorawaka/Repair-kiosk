<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class JobStatusHistory
{
    public static function log(
        int $jobId,
        ?string $fromStatus,
        string $toStatus,
        string $source,
        ?int $changedBy = null,
        ?string $note = null,
        bool $isPublic = true
    ): void {
        $stmt = Database::connection()->prepare(
            'INSERT INTO job_status_history
                (job_id, from_status, to_status, source, changed_by, note, is_public)
             VALUES (:job_id, :from_status, :to_status, :source, :changed_by, :note, :is_public)'
        );
        $stmt->execute([
            'job_id'      => $jobId,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'source'      => $source,
            'changed_by'  => $changedBy,
            'note'        => $note,
            'is_public'   => $isPublic ? 1 : 0,
        ]);
    }
}
