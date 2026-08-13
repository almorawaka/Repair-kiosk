<?php
declare(strict_types=1);

namespace App\Controllers\Kiosk;

use App\Core\Controller;
use App\Core\Request;
use App\Models\RepairJob;
use App\Services\QrService;

final class PrintController extends Controller
{
    public function dropoffSlip(Request $request, string $jobId): void
    {
        $job = RepairJob::find((int) $jobId);
        if ($job === null) {
            http_response_code(404);
            echo 'Job not found.';
            return;
        }

        $this->view('print/dropoff-slip', [
            'job'         => $job,
            'trackUrl'    => QrService::trackUrl($job['public_token']),
            'qrImage'     => QrService::imageUrl(QrService::trackUrl($job['public_token'])),
            'workshopName' => config('app.name'),
        ], 'print');
    }

    public function handoverSlip(Request $request, string $jobId): void
    {
        $job = RepairJob::find((int) $jobId);
        if ($job === null) {
            http_response_code(404);
            echo 'Job not found.';
            return;
        }

        $this->view('print/handover-slip', [
            'job' => $job,
            'workshopName' => config('app.name'),
        ], 'print');
    }
}
