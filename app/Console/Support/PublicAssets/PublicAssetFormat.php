<?php

declare(strict_types=1);

namespace App\Console\Support\PublicAssets;

final class PublicAssetFormat
{
    public static function bytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $index => $unit) {
            if ($value < 1024 || $index === array_key_last($units)) {
                return number_format($value, 2).' '.$unit;
            }

            $value /= 1024;
        }

        return $bytes.' B';
    }

    public static function duration(float $seconds): string
    {
        $seconds = max(0, (int) round($seconds));
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm %02ds', $hours, $minutes, $remainingSeconds);
        }

        if ($minutes > 0) {
            return sprintf('%dm %02ds', $minutes, $remainingSeconds);
        }

        return $remainingSeconds.'s';
    }
}
