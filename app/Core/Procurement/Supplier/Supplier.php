<?php

declare(strict_types=1);

namespace App\Core\Procurement\Supplier;

use App\Core\Shared\Exceptions\DomainException;

final class Supplier
{
    private function __construct(
        private string $id,
        private string $namaPtPengirim,
        private string $namaPtPengirimNormalized,
        private ?string $bankName = null,
        private ?string $bankAccountNumber = null,
    ) {}

    public static function create(
        string $id,
        string $namaPtPengirim,
        ?string $bankName = null,
        ?string $bankAccountNumber = null,
    ): self {
        self::assertValid($id, $namaPtPengirim);

        return new self(
            trim($id),
            trim($namaPtPengirim),
            self::normalizeNamaPtPengirim($namaPtPengirim),
            self::nullable($bankName),
            self::nullable($bankAccountNumber),
        );
    }

    public static function rehydrate(
        string $id,
        string $namaPtPengirim,
        ?string $bankName = null,
        ?string $bankAccountNumber = null,
    ): self {
        return self::create($id, $namaPtPengirim, $bankName, $bankAccountNumber);
    }

    public function rename(string $namaPtPengirim): void
    {
        self::assertValid($this->id, $namaPtPengirim);

        $this->namaPtPengirim = trim($namaPtPengirim);
        $this->namaPtPengirimNormalized = self::normalizeNamaPtPengirim($namaPtPengirim);
    }

    public function updateBankDetails(?string $bankName, ?string $bankAccountNumber): void
    {
        $this->bankName = self::nullable($bankName);
        $this->bankAccountNumber = self::nullable($bankAccountNumber);
    }

    public function bankName(): ?string { return $this->bankName; }
    public function bankAccountNumber(): ?string { return $this->bankAccountNumber; }

    private static function nullable(?string $value): ?string
    {
        return trim($value ?? '') === '' ? null : trim($value);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function namaPtPengirim(): string
    {
        return $this->namaPtPengirim;
    }

    public function namaPtPengirimNormalized(): string
    {
        return $this->namaPtPengirimNormalized;
    }

    private static function assertValid(string $id, string $namaPtPengirim): void
    {
        if (trim($id) === '') {
            throw new DomainException('Supplier id wajib ada.');
        }

        if (trim($namaPtPengirim) === '') {
            throw new DomainException('Nama PT pengirim wajib ada.');
        }
    }

    private static function normalizeNamaPtPengirim(string $namaPtPengirim): string
    {
        $normalized = trim($namaPtPengirim);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return mb_strtolower($normalized);
    }
}
