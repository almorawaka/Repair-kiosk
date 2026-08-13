<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Borrower;
use App\Models\Equipment;
use App\Models\RepairJob;

/**
 * app/Controllers/Api/ScanController.php
 * ---------------------------------------------------------------------
 * Route: POST /api/scan/lookup   (middleware: kiosk, csrf)
 *
 * Every screen where a barcode scanner or QR camera feeds text into the
 * kiosk calls this endpoint before the wizard moves to its next step.
 * The scanner is a keyboard-wedge device — scanner.js buffers the
 * keystrokes, detects the terminating Enter, and POSTs the resulting
 * string here. This controller never trusts that string: it strips
 * control characters a scanner suffix can leave behind, then resolves
 * it against exactly one of three tables depending on which step of
 * the kiosk flow is asking.
 *
 * Request body (JSON or form):
 *   code    string  required  the raw scanned/typed value
 *   purpose string  required  one of: dropoff_equipment | dropoff_borrower | collect
 *
 * The caller (kiosk JS) always knows which screen it's on, so `purpose`
 * is explicit rather than guessed from the shape of `code` — asset
 * tags, borrower codes and QR tokens can all coincidentally look alike
 * in a small pilot deployment, and guessing wrong sends someone else's
 * equipment record onto a stranger's drop-off screen.
 *
 * Response shape is always:
 *   { "ok": bool, "type": string, "message": string, ...extra fields }
 * `type` lets kiosk-dropoff.js / kiosk-collect.js switch on outcome
 * without re-deriving it from field presence.
 * ---------------------------------------------------------------------
 */
final class ScanController extends Controller
{
    private const VALID_PURPOSES = ['dropoff_equipment', 'dropoff_borrower', 'collect'];

    /** Matches TokenService's bin2hex(random_bytes(16)) output. */
    private const TOKEN_PATTERN = '/^[a-f0-9]{32}$/';

    public function lookup(Request $request): void
    {
        $code    = $this->sanitize((string) $request->input('code', ''));
        $purpose = (string) $request->input('purpose', '');

        if ($code === '') {
            $this->json(['ok' => false, 'type' => 'error', 'message' => 'No code was scanned.'], 400);
            return;
        }

        if (!in_array($purpose, self::VALID_PURPOSES, true)) {
            $this->json(['ok' => false, 'type' => 'error', 'message' => 'Unknown scan purpose.'], 400);
            return;
        }

        match ($purpose) {
            'dropoff_equipment' => $this->lookupEquipmentForDropoff($code),
            'dropoff_borrower'  => $this->lookupBorrower($code),
            'collect'           => $this->lookupForCollection($code),
        };
    }

    /**
     * Drop-off, step 1: borrower scans the asset's barcode.
     * Blocks the flow here — not just at INSERT time — so the kiosk can
     * show "this is already on repair, job #RJ-2026-000042" instead of
     * a raw database error after the borrower has filled in the fault
     * description.
     */
    private function lookupEquipmentForDropoff(string $code): void
    {
        $equipment = Equipment::findByAssetTag($code);

        if ($equipment === null) {
            $this->json([
                'ok'      => false,
                'type'    => 'unknown_asset',
                'message' => 'This asset tag is not in the register. Ask a technician for help.',
            ], 404);
            return;
        }

        $openJob = RepairJob::findOpenByEquipmentId((int) $equipment['id']);

        if ($openJob !== null) {
            $this->json([
                'ok'      => false,
                'type'    => 'already_open',
                'message' => 'This item already has an open repair job.',
                'job'     => [
                    'job_number' => $openJob['job_number'],
                    'status'     => $openJob['status'],
                ],
            ], 409);
            return;
        }

        $this->json([
            'ok'        => true,
            'type'      => 'equipment',
            'message'   => 'Equipment found.',
            'equipment' => [
                'id'        => (int) $equipment['id'],
                'asset_tag' => $equipment['asset_tag'],
                'name'      => $equipment['name'],
                'category'  => $equipment['category'],
                'brand'     => $equipment['brand'],
                'model'     => $equipment['model'],
            ],
        ]);
    }

    /**
     * Drop-off, step 2: borrower scans their staff/student ID card.
     * A miss here is not an error — the kiosk falls back to a
     * walk-in name/contact form, so this simply reports "not found"
     * rather than a failure status.
     */
    private function lookupBorrower(string $code): void
    {
        $borrower = Borrower::findByCode($code);

        if ($borrower === null) {
            $this->json([
                'ok'      => false,
                'type'    => 'unknown_borrower',
                'message' => 'ID not recognised. You can continue as a walk-in.',
            ]);
            return;
        }

        $this->json([
            'ok'       => true,
            'type'     => 'borrower',
            'message'  => 'Borrower found.',
            'borrower' => [
                'id'        => (int) $borrower['id'],
                'full_name' => $borrower['full_name'],
                'department' => $borrower['department'],
            ],
        ]);
    }

    /**
     * Collection screen: the printed slip's QR (public_token) is the
     * expected input, but the asset's own barcode is accepted as a
     * fallback in case the slip is lost — the borrower still has the
     * physical item in hand, and it carries its own scannable tag.
     */
    private function lookupForCollection(string $code): void
    {
        $job = preg_match(self::TOKEN_PATTERN, $code) === 1
            ? RepairJob::findByToken($code)
            : $this->findJobByAssetTagFallback($code);

        if ($job === null) {
            $this->json([
                'ok'      => false,
                'type'    => 'unknown',
                'message' => 'No matching repair job was found.',
            ], 404);
            return;
        }

        $statuses = require BASE_PATH . '/app/Config/statuses.php';
        $collectableFrom = $statuses['kiosk_allowed_transitions'] ?? [];

        if (!array_key_exists($job['status'], $collectableFrom)) {
            $this->json([
                'ok'      => false,
                'type'    => 'not_ready',
                'message' => 'This item is not ready for collection yet.',
                'job'     => [
                    'job_number' => $job['job_number'],
                    'status'     => $job['status'],
                    'status_label' => $statuses['meta'][$job['status']]['label'] ?? $job['status'],
                ],
            ], 409);
            return;
        }

        $this->json([
            'ok'      => true,
            'type'    => 'job',
            'message' => 'Ready for collection.',
            'job'     => [
                'id'          => (int) $job['id'],
                'job_number'  => $job['job_number'],
                'status'      => $job['status'],
            ],
        ]);
    }

    private function findJobByAssetTagFallback(string $assetTag): ?array
    {
        $equipment = Equipment::findByAssetTag($assetTag);
        if ($equipment === null) {
            return null;
        }
        return RepairJob::findOpenByEquipmentId((int) $equipment['id']);
    }

    /**
     * Strips control characters (CR/LF/Tab a scanner suffix can add)
     * and trims whitespace. Deliberately does NOT uppercase or reshape
     * the value — asset tags, borrower codes and tokens each have
     * their own casing rules, and normalising here would make a
     * genuinely wrong scan look like a near-miss instead of a miss.
     */
    private function sanitize(string $raw): string
    {
        return trim(preg_replace('/[\x00-\x1F\x7F]/', '', $raw) ?? '');
    }
}
