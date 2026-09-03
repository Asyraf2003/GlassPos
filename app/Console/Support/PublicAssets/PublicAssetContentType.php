<?php

declare(strict_types=1);

namespace App\Console\Support\PublicAssets;

use SplFileInfo;

final class PublicAssetContentType
{
    private const KNOWN = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'mjs' => 'text/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'map' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'eot' => 'application/vnd.ms-fontobject',
        'wasm' => 'application/wasm',
        'html' => 'text/html; charset=utf-8',
        'htm' => 'text/html; charset=utf-8',
        'xml' => 'application/xml; charset=utf-8',
        'txt' => 'text/plain; charset=utf-8',
        'pdf' => 'application/pdf',
    ];

    public static function for(SplFileInfo $file): string
    {
        $known = self::KNOWN[strtolower($file->getExtension())] ?? null;

        if (is_string($known)) {
            return $known;
        }

        $detected = @mime_content_type($file->getPathname());

        return is_string($detected) && $detected !== ''
            ? $detected
            : 'application/octet-stream';
    }
}
