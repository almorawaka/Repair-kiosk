<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\RepairJob;
use App\Services\JobService;
use RuntimeException;

final class JobController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('staff/jobs/index', [
            'jobs'     => RepairJob::listActive(),
            'statuses' => config('statuses')['meta'],
        ], 'staff');
    }

    public function show(Request $request, string $id): void
    {
        $job = RepairJob::find((int) $id);
        if ($job === null) {
            http_response_code(404);
            echo 'Job not found.';
            return;
        }

        $this->view('staff/jobs/show', [
            'job'       => $job,
            'history'   => RepairJob::statusHistory((int) $id),
            'statuses'  => config('statuses'),
        ], 'staff');
    }

    public function updateStatus(Request $request, string $id): void
    {
        $newStatus = (string) $request->input('status', '');
        $note = trim((string) $request->input('note', '')) ?: null;

        try {
            JobService::changeStatus((int) $id, $newStatus, 'staff', Auth::id(), $note);
            $this->flash('success', 'Status updated.');
        } catch (RuntimeException $e) {
            $this->flash('error', $e->getMessage());
        }

        $this->redirect('/staff/jobs/' . $id);
    }
}
