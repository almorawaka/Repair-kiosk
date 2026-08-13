<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $rows = Database::connection()->query(
            "SELECT status, COUNT(*) AS total FROM repair_jobs
             WHERE status NOT IN ('collected','cancelled')
             GROUP BY status"
        )->fetchAll();

        $counts = array_column($rows, 'total', 'status');

        $this->view('staff/dashboard', [
            'counts'   => $counts,
            'statuses' => config('statuses')['meta'],
        ], 'staff');
    }
}
