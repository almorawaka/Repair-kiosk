<?php
declare(strict_types=1);

namespace App\Controllers\Kiosk;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Borrower;
use App\Models\Equipment;
use App\Services\JobService;

/**
 * Kiosk drop-off wizard: scan -> identify -> fault -> confirm -> success.
 * Each step's data is held in the session wizard bag (see Session::wizard*)
 * so nothing touches repair_jobs until Confirm is pressed — an abandoned
 * kiosk session (borrower walks away) never leaves a half-created job.
 */
final class DropoffController extends Controller
{
    private const FLOW = 'dropoff';

    // --- Step 1: scan the asset tag ---------------------------------

    public function showScan(Request $request): void
    {
        $this->view('kiosk/dropoff/scan', []);
    }

    public function handleScan(Request $request): void
    {
        $code = $this->sanitize((string) $request->input('code', ''));

        if ($code === '') {
            $this->flash('error', 'Please scan or enter the asset tag.');
            $this->redirect('/kiosk/dropoff/scan');
            return;
        }

        $equipment = Equipment::findByAssetTag($code);

        if ($equipment === null) {
            $this->flash('error', "Asset tag \"{$code}\" was not found. Ask a technician for help.");
            $this->redirect('/kiosk/dropoff/scan');
            return;
        }

        $openJob = \App\Models\RepairJob::findOpenByEquipmentId((int) $equipment['id']);
        if ($openJob !== null) {
            $this->flash('error', "This item already has an open job ({$openJob['job_number']}).");
            $this->redirect('/kiosk/dropoff/scan');
            return;
        }

        Session::wizardPut(self::FLOW, 'equipment_id', (int) $equipment['id']);
        Session::wizardPut(self::FLOW, 'equipment_name', $equipment['name']);
        Session::wizardPut(self::FLOW, 'asset_tag', $equipment['asset_tag']);

        $this->redirect('/kiosk/dropoff/identify');
    }

    // --- Step 2: identify the borrower ------------------------------

    public function showIdentify(Request $request): void
    {
        if (!Session::wizard(self::FLOW)['equipment_id'] ?? false) {
            $this->redirect('/kiosk/dropoff/scan');
            return;
        }
        $this->view('kiosk/dropoff/identify', ['wizard' => Session::wizard(self::FLOW)]);
    }

    public function handleIdentify(Request $request): void
    {
        $code = $this->sanitize((string) $request->input('code', ''));
        $walkinName = trim((string) $request->input('walkin_name', ''));

        if ($code !== '') {
            $borrower = Borrower::findByCode($code);
            if ($borrower === null) {
                $this->flash('error', "ID \"{$code}\" was not recognised. Enter your name to continue as a walk-in, or try scanning again.");
                $this->redirect('/kiosk/dropoff/identify');
                return;
            }
            Session::wizardPut(self::FLOW, 'borrower_id', (int) $borrower['id']);
            Session::wizardPut(self::FLOW, 'borrower_name', $borrower['full_name']);
        } elseif ($walkinName !== '') {
            Session::wizardPut(self::FLOW, 'walkin_name', $walkinName);
            Session::wizardPut(self::FLOW, 'walkin_contact', trim((string) $request->input('walkin_contact', '')));
        } else {
            $this->flash('error', 'Scan your ID or enter your name to continue.');
            $this->redirect('/kiosk/dropoff/identify');
            return;
        }

        $this->redirect('/kiosk/dropoff/fault');
    }

    // --- Step 3: describe the fault ----------------------------------

    public function showFault(Request $request): void
    {
        $this->view('kiosk/dropoff/fault', ['wizard' => Session::wizard(self::FLOW)]);
    }

    public function handleFault(Request $request): void
    {
        $description = trim((string) $request->input('fault_description', ''));

        if ($description === '' || mb_strlen($description) < 5) {
            $this->flash('error', 'Please describe the problem in a few words.');
            $this->redirect('/kiosk/dropoff/fault');
            return;
        }

        Session::wizardPut(self::FLOW, 'fault_description', $description);
        $this->redirect('/kiosk/dropoff/confirm');
    }

    // --- Step 4: confirm and create the job ---------------------------

    public function showConfirm(Request $request): void
    {
        $this->view('kiosk/dropoff/confirm', ['wizard' => Session::wizard(self::FLOW)]);
    }

    public function handleConfirm(Request $request): void
    {
        $wizard = Session::wizard(self::FLOW);

        if (empty($wizard['equipment_id']) || empty($wizard['fault_description'])) {
            $this->flash('error', 'Something went wrong — please start again.');
            $this->redirect('/kiosk/dropoff/scan');
            return;
        }

        $job = JobService::createJob($wizard);
        Session::wizardClear(self::FLOW);

        $this->redirect('/kiosk/dropoff/success/' . $job['id']);
    }

    public function success(Request $request, string $jobId): void
    {
        $job = \App\Models\RepairJob::find((int) $jobId);
        if ($job === null) {
            $this->redirect('/kiosk');
            return;
        }

        $this->view('kiosk/dropoff/success', [
            'job'      => $job,
            'trackUrl' => \App\Services\QrService::trackUrl($job['public_token']),
            'qrImage'  => \App\Services\QrService::imageUrl(
                \App\Services\QrService::trackUrl($job['public_token'])
            ),
        ]);
    }

    private function sanitize(string $raw): string
    {
        return trim(preg_replace('/[\x00-\x1F\x7F]/', '', $raw) ?? '');
    }
}
