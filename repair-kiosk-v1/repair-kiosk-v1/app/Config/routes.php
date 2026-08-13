<?php
declare(strict_types=1);

/**
 * app/Config/routes.php
 * ---------------------------------------------------------------------
 * Route table read by app/Core/Router.php. Format:
 *
 *   'METHOD /path/{param}' => [ControllerClass::class, 'method', middleware[]]
 *
 * - {param} segments are captured and passed as controller method args,
 *   in order, after Request $request.
 * - middleware[] is a list of short keys resolved against the
 *   Middleware/ classes (see the map at the bottom of this file).
 *   Omit it (or pass []) for a route with no middleware.
 *
 * Routes are grouped by area to mirror app/Controllers/*. Keep that
 * mirroring intact — it's the only thing stopping this file from
 * turning into a junk drawer as the project grows.
 * ---------------------------------------------------------------------
 */

use App\Controllers\Kiosk\HomeController;
use App\Controllers\Kiosk\DropoffController;
use App\Controllers\Kiosk\CollectController;
use App\Controllers\Kiosk\PrintController;

use App\Controllers\Staff\AuthController;
use App\Controllers\Staff\DashboardController;
use App\Controllers\Staff\JobController;
use App\Controllers\Staff\EquipmentController;
use App\Controllers\Staff\BorrowerController;
use App\Controllers\Staff\UserController;
use App\Controllers\Staff\ReportController;

use App\Controllers\PublicController;

use App\Controllers\Api\ScanController;
use App\Controllers\Api\JobStatusController;

return [

    // ===============================================================
    // ROOT
    // ===============================================================
    'GET /' => [HomeController::class, 'redirectToKiosk'],

    // ===============================================================
    // KIOSK  (touchscreen terminal — IP-restricted, no login)
    // ===============================================================
    'GET /kiosk'                       => [HomeController::class, 'index', ['kiosk']],

    // --- Drop-off wizard -------------------------------------------
    'GET /kiosk/dropoff/scan'          => [DropoffController::class, 'showScan', ['kiosk']],
    'POST /kiosk/dropoff/scan'          => [DropoffController::class, 'handleScan', ['kiosk', 'csrf']],

    'GET /kiosk/dropoff/identify'      => [DropoffController::class, 'showIdentify', ['kiosk']],
    'POST /kiosk/dropoff/identify'      => [DropoffController::class, 'handleIdentify', ['kiosk', 'csrf']],

    'GET /kiosk/dropoff/fault'         => [DropoffController::class, 'showFault', ['kiosk']],
    'POST /kiosk/dropoff/fault'         => [DropoffController::class, 'handleFault', ['kiosk', 'csrf']],

    'GET /kiosk/dropoff/accessories'   => [DropoffController::class, 'showAccessories', ['kiosk']],
    'POST /kiosk/dropoff/accessories'   => [DropoffController::class, 'handleAccessories', ['kiosk', 'csrf']],

    'GET /kiosk/dropoff/confirm'       => [DropoffController::class, 'showConfirm', ['kiosk']],
    'POST /kiosk/dropoff/confirm'       => [DropoffController::class, 'handleConfirm', ['kiosk', 'csrf']],

    'GET /kiosk/dropoff/success/{jobId}' => [DropoffController::class, 'success', ['kiosk']],

    // --- Collection wizard -------------------------------------------
    'GET /kiosk/collect/scan'          => [CollectController::class, 'showScan', ['kiosk']],
    'POST /kiosk/collect/scan'          => [CollectController::class, 'handleScan', ['kiosk', 'csrf']],

    'GET /kiosk/collect/verify/{jobId}' => [CollectController::class, 'showVerify', ['kiosk']],
    'POST /kiosk/collect/verify/{jobId}' => [CollectController::class, 'handleVerify', ['kiosk', 'csrf']],

    'GET /kiosk/collect/sign/{jobId}'  => [CollectController::class, 'showSign', ['kiosk']],
    'POST /kiosk/collect/sign/{jobId}'  => [CollectController::class, 'handleSign', ['kiosk', 'csrf']],

    'GET /kiosk/collect/success/{jobId}' => [CollectController::class, 'success', ['kiosk']],

    // --- Printing (renders slip HTML, opened in a print-only tab) --
    'GET /kiosk/print/dropoff/{jobId}'  => [PrintController::class, 'dropoffSlip', ['kiosk']],
    'GET /kiosk/print/handover/{jobId}' => [PrintController::class, 'handoverSlip', ['kiosk']],

    // ===============================================================
    // PUBLIC  (the QR destination — no auth, read-only, rate-limited)
    // ===============================================================
    'GET /track/{token}' => [PublicController::class, 'track', ['throttle']],

    // ===============================================================
    // STAFF PANEL  (technicians + admins — login required)
    // ===============================================================
    'GET /staff/login'  => [AuthController::class, 'showLogin'],
    'POST /staff/login'  => [AuthController::class, 'handleLogin', ['csrf', 'throttle']],
    'POST /staff/logout' => [AuthController::class, 'logout', ['auth']],

    'GET /staff/dashboard' => [DashboardController::class, 'index', ['auth']],

    // --- Jobs --------------------------------------------------------
    'GET /staff/jobs'                    => [JobController::class, 'index', ['auth']],
    'GET /staff/jobs/{id}'               => [JobController::class, 'show', ['auth']],
    'POST /staff/jobs/{id}/status'        => [JobController::class, 'updateStatus', ['auth', 'csrf']],
    'POST /staff/jobs/{id}/assign'        => [JobController::class, 'assign', ['auth', 'csrf']],
    'POST /staff/jobs/{id}/notes'         => [JobController::class, 'addNote', ['auth', 'csrf']],
    'POST /staff/jobs/{id}/photos'        => [JobController::class, 'uploadPhoto', ['auth', 'csrf']],
    'POST /staff/jobs/{id}/cancel'        => [JobController::class, 'cancel', ['auth', 'csrf', 'role:admin']],

    // --- Equipment register ------------------------------------------
    'GET /staff/equipment'          => [EquipmentController::class, 'index', ['auth']],
    'GET /staff/equipment/create'   => [EquipmentController::class, 'create', ['auth']],
    'POST /staff/equipment'          => [EquipmentController::class, 'store', ['auth', 'csrf']],
    'GET /staff/equipment/{id}/edit' => [EquipmentController::class, 'edit', ['auth']],
    'POST /staff/equipment/{id}'     => [EquipmentController::class, 'update', ['auth', 'csrf']],
    'POST /staff/equipment/{id}/delete' => [EquipmentController::class, 'delete', ['auth', 'csrf', 'role:admin']],

    // --- Borrowers -----------------------------------------------------
    'GET /staff/borrowers'         => [BorrowerController::class, 'index', ['auth']],
    'GET /staff/borrowers/create'  => [BorrowerController::class, 'create', ['auth']],
    'POST /staff/borrowers'         => [BorrowerController::class, 'store', ['auth', 'csrf']],
    'GET /staff/borrowers/{id}/edit' => [BorrowerController::class, 'edit', ['auth']],
    'POST /staff/borrowers/{id}'    => [BorrowerController::class, 'update', ['auth', 'csrf']],

    // --- Staff users (admin only) --------------------------------------
    'GET /staff/users'         => [UserController::class, 'index', ['auth', 'role:admin']],
    'GET /staff/users/create'  => [UserController::class, 'create', ['auth', 'role:admin']],
    'POST /staff/users'         => [UserController::class, 'store', ['auth', 'role:admin', 'csrf']],
    'GET /staff/users/{id}/edit' => [UserController::class, 'edit', ['auth', 'role:admin']],
    'POST /staff/users/{id}'    => [UserController::class, 'update', ['auth', 'role:admin', 'csrf']],
    'POST /staff/users/{id}/deactivate' => [UserController::class, 'deactivate', ['auth', 'role:admin', 'csrf']],

    // --- Reports ---------------------------------------------------------
    'GET /staff/reports'             => [ReportController::class, 'index', ['auth']],
    'GET /staff/reports/turnaround'  => [ReportController::class, 'turnaround', ['auth']],
    'GET /staff/reports/export.csv'  => [ReportController::class, 'exportCsv', ['auth']],

    // ===============================================================
    // JSON API  (AJAX endpoints called from kiosk/staff JS)
    // ===============================================================
    'POST /api/scan/lookup'        => [ScanController::class, 'lookup', ['kiosk', 'csrf']],
    'GET /api/jobs/{id}/status'   => [JobStatusController::class, 'poll', ['throttle']],

];
