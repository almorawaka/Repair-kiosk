<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Models\RepairJob;

/**
 * app/Controllers/Api/JobStatusController.php
 * ---------------------------------------------------------------------
 * Route: GET /api/jobs/{id}/status   (middleware: throttle — no auth)
 *
 * Backs the auto-refresh on the public track page (app/Views/public/
 * track.php polls this every ~20s instead of reloading the whole page)
 * and any kiosk "waiting for status" screen.
 *
 * IMPORTANT — the {id} route segment must be the job's public_token,
 * NOT its numeric primary key, even though routes.php names the
 * placeholder generically. This route carries no 'auth' middleware by
 * design (a borrower's phone is never logged in), which means a
 * sequential integer here would let anyone enumerate every job in the
 * system just by counting 1, 2, 3... The 32-character random token is
 * what makes "you can only see your own job" actually true. This
 * controller enforces that shape server-side rather than trusting the
 * router or the caller — see validateToken() below. If routes.php is
 * ever touched again, renaming that placeholder to {token} is worth
 * doing so the constraint is visible in the route table itself.
 *
 * The response is intentionally thin. This is an unauthenticated
 * endpoint, so it returns only what the public track page already
 * shows: no fault description, no borrower details, no cost, no
 * internal notes. See app/Config/statuses.php `meta.*.public_label`
 * for the borrower-facing wording — this endpoint uses the same
 * source so the polled JSON and the server-rendered page never drift
 * out of sync with each other.
 * ---------------------------------------------------------------------
 */
final class JobStatusController extends Controller
{
    private const TOKEN_PATTERN = '/^[a-f0-9]{32}$/';

    public function poll(Request $request, string $token): void
    {
        if (!$this->validateToken($token)) {
            $this->json([
                'ok'      => false,
                'message' => 'Invalid tracking code.',
            ], 400);
            return;
        }

        $job = RepairJob::findByToken($token);

        if ($job === null) {
            $this->json([
                'ok'      => false,
                'message' => 'No repair job found for this tracking code.',
            ], 404);
            return;
        }

        $statuses = require BASE_PATH . '/app/Config/statuses.php';
        $meta = $statuses['meta'][$job['status']] ?? null;

        // Defensive: a status value on the row that isn't in statuses.php
        // means the DB ENUM and this config have drifted apart. Fail
        // loudly rather than showing the borrower a blank/broken status.
        if ($meta === null) {
            $this->json([
                'ok'      => false,
                'message' => 'Status temporarily unavailable. Please contact the workshop.',
            ], 500);
            return;
        }

        $this->json([
            'ok'  => true,
            'job' => [
                'job_number'          => $job['job_number'],
                'equipment_name'      => $job['equipment_name'],
                'status'              => $job['status'],
                'status_label'        => $meta['public_label'],
                'is_terminal'         => $meta['is_terminal'],
                'dropped_off_at'      => $job['dropped_off_at'],
                'estimated_ready_date' => $job['estimated_ready_date'],
                'ready_at'            => $job['ready_at'],
                'collected_at'        => $job['collected_at'],
                'updated_at'          => $job['updated_at'],
            ],
        ]);
    }

    private function validateToken(string $token): bool
    {
        return preg_match(self::TOKEN_PATTERN, $token) === 1;
    }
}
