<?php
declare(strict_types=1);

namespace App\Services;

final class TokenService
{
    /** 32 lowercase hex chars — matches the pattern every controller validates against. */
    public static function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
