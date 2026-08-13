<?php
declare(strict_types=1);

namespace App\Controllers\Kiosk;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Equipment;
use App\Models\RepairJob;
use App\Services\JobService;

final class CollectController extends Controller
{
    private const TOKEN_PATTERN = '/^[a-f0-9]{32}$/';

    public function showScan(Request $request): void
    {
        $this->view('kiosk/collect/scan', []);
    }

    public function handleScan(Request $request): void
    {
        $code = trim(preg_replace('/[\x00-\x1F\x7F]/', '', (string) $request->input('code', '')) ?? '');

        if ($code === '') {
            $this->flash('error', 'Please scan the slip or the item\'s asset tag.');
            $this->redirect('/kiosk/collect/scan');
            return;
        }

        $job = preg_match(self::TOKEN_PATTERN, $code) === 1
            ? RepairJob::findByToken($code)
            : $this->findByAssetTagFallback($code);

        if ($job === null) {
            $this->flash('error', 'No matching repair job was found.');
            $this->redirect('/kiosk/collect/scan');
            return;
        }

        $statuses = config('statuses');
        if (!array_key_exists($job['status'], $statuses['kiosk_allowed_transitions'] ?? [])) {
            $label = $statuses['meta'][$job['status']]['label'] ?? $job['status'];
            $this->flash('error', "{$job['job_number']} is not ready yet (current status: {$label}).");
            $this->redirect('/kiosk/collect/scan');
            return;
        }

        $this->redirect('/kiosk/collect/verify/' . $job['id']);
    }

    public function showVerify(Request $request, string $jobId): void
    {
        $job = RepairJob::find((int) $jobId);
        if ($job === null) {
            $this->redirect('/kiosk/collect/scan');
            return;
        }
        $this->view('kiosk/collect/verify', ['job' => $job]);
    }

    public function handleVerify(Request $request, string $jobId): void
    {
        $job = RepairJob::find((int) $jobId);
        if ($job === null) {
            $this->redirect('/kiosk/collect/scan');
            return;
        }

        try {
            JobService::changeStatus((int) $jobId, 'collected', 'kiosk', null, 'Collected at kiosk.');
        } catch (\RuntimeException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/kiosk/collect/scan');
            return;
        }

        $this->redirect('/kiosk/collect/success/' . $jobId);
    }

    // Signature capture is a v2 feature — see docs/setup.md. v1 confirms
    // collection with the button on the verify screen only.
    public function showSign(Request $request, string $jobId): void
    {
        $this->redirect('/kiosk/collect/verify/' . $jobId);
    }

    public function handleSign(Request $request, string $jobId): void
    {
        $this->redirect('/kiosk/collect/verify/' . $jobId);
    }

    public function success(Request $request, string $jobId): void
    {
        $job = RepairJob::find((int) $jobId);
        if ($job === null) {
            $this->redirect('/kiosk');
            return;
        }
        $this->view('kiosk/collect/success', ['job' => $job]);
    }

    private function findByAssetTagFallback(string $assetTag): ?array
    {
        $equipment = Equipment::findByAssetTag($assetTag);
        if ($equipment === null) {
            return null;
        }
        $open = RepairJob::findOpenByEquipmentId((int) $equipment['id']);
        if ($open === null) {
            return null;
        }
        return RepairJob::find((int) $open['id']);
    }
}
