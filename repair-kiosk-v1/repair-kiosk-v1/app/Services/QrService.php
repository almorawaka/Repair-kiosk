<?php
declare(strict_types=1);

namespace App\Services;

/**
 * v1 generates QR images via a free public API (api.qrserver.com)
 * rather than a composer package, so the kiosk works before you've
 * touched Composer at all. This REQUIRES the workshop network to have
 * internet access reaching that domain. For a fully offline kiosk,
 * swap this for endroid/qr-code (composer require endroid/qr-code)
 * and generate PNGs locally into storage/qrcodes/ instead — the public
 * method signatures below are written so that swap doesn't touch any
 * calling code.
 */
final class QrService
{
    public static function trackUrl(string $token): string
    {
        return rtrim((string) config('app.track_base_url'), '/') . '/' . $token;
    }

    public static function imageUrl(string $data, int $size = 240): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
            . '&data=' . urlencode($data);
    }
}
