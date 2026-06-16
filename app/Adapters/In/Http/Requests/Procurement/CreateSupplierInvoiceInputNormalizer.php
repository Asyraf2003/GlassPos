<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Procurement;

final class CreateSupplierInvoiceInputNormalizer
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function normalize(array $input): array
    {
        $autoReceive = $input['auto_receive'] ?? null;

        return [
            'nomor_faktur' => $this->trimOrNull($input['nomor_faktur'] ?? null),
            'nama_pt_pengirim' => $this->trimOrNull($input['nama_pt_pengirim'] ?? null),
            'tanggal_pengiriman' => $this->trimOrNull($input['tanggal_pengiriman'] ?? null),
            'tanggal_terima' => $this->trimOrNull($input['tanggal_terima'] ?? null),
            'auto_receive' => is_bool($autoReceive) ? $autoReceive : $this->toNullableBool($autoReceive),
            'tax_input' => $this->trimScalarOrNull($input['tax_input'] ?? null),
        ];
    }

    private function trimScalarOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function trimOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function toNullableBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value === 1 || $value === '1' || $value === true || $value === 'true') {
            return true;
        }

        if ($value === 0 || $value === '0' || $value === false || $value === 'false') {
            return false;
        }

        return null;
    }
}
