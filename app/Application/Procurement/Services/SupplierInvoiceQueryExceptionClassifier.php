<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Application\Shared\DTO\Result;
use Throwable;

final class SupplierInvoiceQueryExceptionClassifier
{
    public function classify(Throwable $e): ?Result
    {
        $sqlState = $this->sqlState($e);
        $message = mb_strtolower($e->getMessage());

        if ($sqlState === '23000' && (
            str_contains($message, 'si_active_invoice_number_unique')
            || str_contains($message, 'supplier_invoices.active_nomor_faktur_normalized')
        )) {
            return Result::failure(
                'Nomor faktur sudah dipakai oleh nota supplier aktif.',
                ['supplier_invoice' => ['SUPPLIER_INVOICE_DUPLICATE_NUMBER']]
            );
        }

        if (
            $sqlState === '23000'
            && str_contains($message, 'sil_supplier_invoice_revision_product_unique')
        ) {
            return Result::failure(
                'Produk yang sama tidak boleh muncul lebih dari sekali dalam satu faktur supplier.',
                ['supplier_invoice' => ['SUPPLIER_INVOICE_DUPLICATE_PRODUCT']]
            );
        }

        if (
            ($sqlState === '22003' || str_contains($message, 'out of range'))
            && (str_contains($message, 'grand_total_rupiah') || str_contains($message, 'supplier_invoices'))
        ) {
            return Result::failure(
                'Total keseluruhan nota melebihi batas penyimpanan sistem. Kurangi total rincian lalu simpan lagi.',
                ['supplier_invoice' => ['SUPPLIER_INVOICE_GRAND_TOTAL_OUT_OF_RANGE']]
            );
        }

        return null;
    }

    private function sqlState(Throwable $e): string
    {
        $errorInfo = [];

        if (property_exists($e, 'errorInfo')) {
            $value = $e->errorInfo;

            if (is_array($value)) {
                $errorInfo = $value;
            }
        }

        return (string) ($errorInfo[0] ?? $e->getCode());
    }
}
