<?php

declare(strict_types=1);

namespace App\Application\Reporting\DTO;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class SupplierPayableReportPageQuery
{
    private function __construct(
        private readonly string $periodMode,
        private readonly ?string $referenceDate,
        private readonly ?string $dateFrom,
        private readonly ?string $dateTo,
    ) {
    }

    public static function fromValidated(array $validated): self
    {
        return new self(
            is_string($validated['period_mode'] ?? null) ? $validated['period_mode'] : 'all',
            is_string($validated['reference_date'] ?? null) ? $validated['reference_date'] : null,
            is_string($validated['date_from'] ?? null) ? $validated['date_from'] : null,
            is_string($validated['date_to'] ?? null) ? $validated['date_to'] : null,
        );
    }

    public function fromShipmentDate(): ?string
    {
        if ($this->periodMode === 'all') {
            return null;
        }

        $reference = $this->resolvedReferenceDate();

        return match ($this->periodMode) {
            'custom' => $this->dateFrom ?? $reference->toDateString(),
            'weekly' => $reference->startOfWeek(CarbonInterface::MONDAY)->toDateString(),
            'monthly' => $reference->startOfMonth()->toDateString(),
            default => $reference->toDateString(),
        };
    }

    public function toShipmentDate(): ?string
    {
        if ($this->periodMode === 'all') {
            return null;
        }

        $reference = $this->resolvedReferenceDate();

        return match ($this->periodMode) {
            'custom' => $this->dateTo ?? $reference->toDateString(),
            'weekly' => $reference->endOfWeek(CarbonInterface::SUNDAY)->toDateString(),
            'monthly' => $reference->endOfMonth()->toDateString(),
            default => $reference->toDateString(),
        };
    }

    public function referenceDate(): string
    {
        return $this->resolvedReferenceDate()->toDateString();
    }

    public function toViewData(): array
    {
        return [
            'period_mode' => $this->periodMode,
            'reference_date' => $this->referenceDate(),
            'date_from' => $this->fromShipmentDate(),
            'date_to' => $this->toShipmentDate(),
            'range_label' => $this->periodMode === 'all' ? 'Seluruh Periode' : $this->fromShipmentDate() . ' s/d ' . $this->toShipmentDate(),
        ];
    }

    private function resolvedReferenceDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->referenceDate ?? $this->dateTo ?? $this->dateFrom ?? 'today');
    }
}
