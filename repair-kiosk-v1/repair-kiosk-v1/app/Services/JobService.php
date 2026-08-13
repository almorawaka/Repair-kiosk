<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Equipment;
use App\Models\JobStatusHistory;
use App\Models\RepairJob;
use RuntimeException;

/**
 * The single place repair_jobs.status is allowed to change. Every
 * controller — kiosk or staff — MUST go through changeStatus(), never
 * update the row directly. This is what makes app/Config/statuses.php
 * the real state machine instead of decorative documentation.
 */
final class JobService
{
    public static function createJob(array $data): array
    {
        $statuses = config('statuses');
        $token = TokenService::generate();
        $jobNumber = RepairJob::nextJobNumber();

        $jobId = RepairJob::create([
            'job_number'        => $jobNumber,
            'public_token'      => $token,
            'equipment_id'      => $data['equipment_id'],
            'borrower_id'       => $data['borrower_id'] ?? null,
            'walkin_name'       => $data['walkin_name'] ?? null,
            'walkin_contact'    => $data['walkin_contact'] ?? null,
            'fault_description' => $data['fault_description'],
            'status'            => $statuses['initial_status'],
        ]);

        Equipment::setStatus((int) $data['equipment_id'], 'on_repair');

        JobStatusHistory::log(
            jobId: $jobId,
            fromStatus: null,
            toStatus: $statuses['initial_status'],
            source: 'kiosk',
            note: 'Dropped off at kiosk.'
        );

        return ['id' => $jobId, 'job_number' => $jobNumber, 'public_token' => $token];
    }

    /**
     * @throws RuntimeException if the transition is not allowed from
     *         the job's current status, or (when $source === 'kiosk')
     *         not in the kiosk's narrower allowlist.
     */
    public static function changeStatus(
        int $jobId,
        string $toStatus,
        string $source,
        ?int $userId = null,
        ?string $note = null
    ): void {
        $statuses = config('statuses');
        $job = RepairJob::find($jobId);

        if ($job === null) {
            throw new RuntimeException('Job not found.');
        }

        $fromStatus = $job['status'];
        $allowed = $statuses['transitions'][$fromStatus] ?? [];

        if (!in_array($toStatus, $allowed, true)) {
            throw new RuntimeException("Cannot move job from '{$fromStatus}' to '{$toStatus}'.");
        }

        if ($source === 'kiosk') {
            $kioskAllowed = $statuses['kiosk_allowed_transitions'][$fromStatus] ?? [];
            if (!in_array($toStatus, $kioskAllowed, true)) {
                throw new RuntimeException('This change must be made by a staff member.');
            }
        }

        $timestampFields = match ($toStatus) {
            'assessing'             => ['assessed_at'],
            'ready_for_collection'  => ['ready_at'],
            'collected'             => ['collected_at'],
            default                 => [],
        };

        RepairJob::updateStatus($jobId, $toStatus, $timestampFields);

        JobStatusHistory::log(
            jobId: $jobId,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            source: $source,
            changedBy: $userId,
            note: $note,
            isPublic: true
        );

        // Closing a job frees the equipment for a future drop-off.
        // Everything else leaves it "on_repair" — including
        // 'unrepairable', which still sits at the counter until collected.
        if (in_array($toStatus, ['collected', 'cancelled'], true)) {
            Equipment::setStatus((int) $job['equipment_id'], 'available');
        }
    }
}
