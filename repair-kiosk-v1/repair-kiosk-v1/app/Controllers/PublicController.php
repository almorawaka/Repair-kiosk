<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\RepairJob;

final class PublicController extends Controller
{
    private const TOKEN_PATTERN = '/^[a-f0-9]{32}$/';

    public function track(Request $request, string $token): void
    {
        if (preg_match(self::TOKEN_PATTERN, $token) !== 1) {
            $this->view('public/not-found', [], 'public');
            return;
        }

        $job = RepairJob::findByToken($token);
        if ($job === null) {
            $this->view('public/not-found', [], 'public');
            return;
        }

        $statuses = config('statuses');
        $history = array_filter(
            RepairJob::statusHistory((int) $job['id']),
            fn ($row) => (int) $row['is_public'] === 1
        );

        $this->view('public/track', [
            'job'      => $job,
            'meta'     => $statuses['meta'][$job['status']] ?? null,
            'allMeta'  => $statuses['meta'],
            'history'  => $history,
        ], 'public');
    }
}
