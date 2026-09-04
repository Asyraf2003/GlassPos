<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note\Queries;

use App\Core\Note\Note\Note;
use Illuminate\Support\Carbon;
use Throwable;

final class CashierNoteHistoryValueFormatter
{
    public function customerLabel(string $name, ?string $phone): string
    {
        $phoneText = $phone !== null ? trim($phone) : '';

        return $phoneText === '' ? trim($name) : trim($name).' / '.$phoneText;
    }

    public function workSummary(int $openCount, int $doneCount, int $canceledCount): string
    {
        return sprintf('Belum Selesai: %d • Selesai: %d • Batal: %d', $openCount, $doneCount, $canceledCount);
    }

    public function lineSummary(int $openCount, int $closeCount, int $refundCount): string
    {
        $parts = [];

        if ($openCount > 0) {
            $parts[] = sprintf('%d Belum Selesai', $openCount);
        }

        if ($closeCount > 0) {
            $parts[] = sprintf('%d Selesai', $closeCount);
        }

        if ($refundCount > 0) {
            $parts[] = sprintf('%d Dikembalikan', $refundCount);
        }

        return $parts === [] ? 'Belum ada rincian.' : implode(', ', $parts);
    }

    public function focusStatus(string $noteState, int $outstanding, int $openWorkCount): string
    {
        return match (true) {
            $noteState === Note::STATE_REFUNDED => 'Selesai',
            $outstanding > 0 && $openWorkCount > 0 => 'Tagihan & pekerjaan aktif',
            $outstanding > 0 => 'Sisa tagihan',
            $openWorkCount > 0 => 'Pekerjaan aktif',
            default => 'Selesai',
        };
    }

    public function domainStatus(string $noteState, int $canceledWorkCount): ?string
    {
        if ($noteState === Note::STATE_REFUNDED) {
            return 'Dikembalikan';
        }

        return $canceledWorkCount > 0 ? 'Ada pekerjaan batal' : null;
    }

    public function date(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $text = (string) $value;

        if (preg_match('/^\\d{2}\\/\\d{2}\\/\\d{4}/', $text) === 1) {
            return $text;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (Throwable) {
            return $text;
        }
    }

    public function dateTime(mixed $createdAt, mixed $fallbackDate): string
    {
        if ($createdAt === null || $createdAt === '') {
            return $this->date($fallbackDate);
        }

        try {
            return Carbon::parse($createdAt)->format('d/m/Y H:i');
        } catch (Throwable) {
            return $this->date($fallbackDate);
        }
    }

    public function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
