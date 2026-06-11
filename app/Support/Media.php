<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media
{
    /**
     * Resolve an image field to a full URL.
     * - null/empty  => null
     * - http(s)://  => returned as-is (seed uses remote URLs)
     * - otherwise   => served from the public storage disk
     */
    public static function url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
