<?php
declare(strict_types=1);

/**
 * app/Config/app.php
 * ---------------------------------------------------------------------
 * Core application settings. Everything here reads from $_ENV, which is
 * populated by vlucas/phpdotenv from the .env file at bootstrap time
 * (see public/index.php). Never hardcode secrets in this file — it is
 * committed to git; .env is not.
 *
 * Access anywhere via the config() helper, e.g.:
 *     config('app.name')
 *     config('app.kiosk_idle_seconds')
 * ---------------------------------------------------------------------
 */

return [

    // -------------------------------------------------------------
    // Identity
    // -------------------------------------------------------------
    'name'        => env('APP_NAME', 'Repair Workshop Kiosk'),
    'env'         => env('APP_ENV', 'production'),      // local | staging | production
    'debug'       => env('APP_DEBUG', false),
    'url'         => rtrim(env('APP_URL', 'http://localhost'), '/'),
    'timezone'    => env('APP_TIMEZONE', 'Asia/Colombo'),
    'locale'      => env('APP_LOCALE', 'en'),

    // -------------------------------------------------------------
    // Kiosk behaviour
    // -------------------------------------------------------------
    // Seconds of inactivity before the kiosk screen resets to the
    // idle/home screen. idle-reset.js reads this via a data attribute
    // rendered into the kiosk layout.
    'kiosk_idle_seconds' => (int) env('KIOSK_IDLE_SECONDS', 90),

    // Comma-separated list of IPs allowed to hit /kiosk/* routes.
    // Enforced by KioskMiddleware. Leave empty in local dev to disable
    // the check entirely.
    'kiosk_allowed_ips' => array_filter(array_map(
        'trim',
        explode(',', env('KIOSK_ALLOWED_IPS', ''))
    )),

    // Default number of days added to dropped_off_at to pre-fill
    // estimated_ready_date on the drop-off form. Overridden per job.
    'default_sla_days' => (int) env('DEFAULT_SLA_DAYS', 5),

    // -------------------------------------------------------------
    // Public tracking page
    // -------------------------------------------------------------
    // Base URL encoded into the QR code. Must be reachable from a
    // borrower's phone, so on local dev this needs to be your LAN IP,
    // not "localhost" — a phone cannot resolve that.
    // Example: http://192.168.1.20/repair-kiosk/public/track
    'track_base_url' => rtrim(
        env('TRACK_BASE_URL', env('APP_URL', 'http://localhost') . '/track'),
        '/'
    ),

    // -------------------------------------------------------------
    // Sessions
    // -------------------------------------------------------------
    'session' => [
        'name'            => 'repair_kiosk_session',
        'lifetime_minutes' => (int) env('SESSION_LIFETIME', 120),
        // Staff panel sessions; kiosk screens are intentionally
        // sessionless / stateless between transactions where possible.
        'secure_cookie'   => filter_var(
            env('SESSION_SECURE_COOKIE', false),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],

    // -------------------------------------------------------------
    // Uploads
    // -------------------------------------------------------------
    'uploads' => [
        'max_photo_bytes'   => 5 * 1024 * 1024, // 5 MB per photo
        'allowed_mime'      => ['image/jpeg', 'image/png', 'image/webp'],
        'storage_path'      => BASE_PATH . '/storage/uploads/jobs',
        'qr_cache_path'     => BASE_PATH . '/storage/qrcodes',
    ],

    // -------------------------------------------------------------
    // Logging
    // -------------------------------------------------------------
    'log_path' => BASE_PATH . '/storage/logs/app.log',
];
