<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\SupplierPayment\SupplierPayment;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Procurement\SupplierPaymentReaderPort;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class DatabaseSupplierPaymentReaderAdapter implements SupplierPaymentReaderPort
{
    public function getTotalPaidBySupplierInvoiceId(string $supplierInvoiceId): Money
    {
        $totalPaid = (int) DB::table('supplier_payments')
            ->leftJoin(
                'supplier_payment_reversals',
                'supplier_payment_reversals.supplier_payment_id',
                '=',
                'supplier_payments.id'
            )
            ->where('supplier_payments.supplier_invoice_id', $supplierInvoiceId)
            ->whereNull('supplier_payment_reversals.id')
            ->sum('supplier_payments.amount_rupiah');

        return Money::fromInt($totalPaid);
    }

    public function getById(string $supplierPaymentId): ?SupplierPayment
    {
        return $this->find($supplierPaymentId, false);
    }

    public function getByIdForUpdate(string $supplierPaymentId): ?SupplierPayment
    {
        return $this->find($supplierPaymentId, true);
    }

    private function find(string $supplierPaymentId, bool $lock): ?SupplierPayment
    {
        $query = DB::table('supplier_payments')->where('id', $supplierPaymentId);

        if ($lock) {
            $query->lockForUpdate();
        }

        $row = $query->first([
            'id',
            'supplier_invoice_id',
            'amount_rupiah',
            'paid_at',
            'proof_status',
            'proof_storage_path',
        ]);

        return $row !== null ? $this->mapRowToPayment($row) : null;
    }

    public function listBySupplierInvoiceId(string $supplierInvoiceId): array
    {
        return DB::table('supplier_payments')
            ->where('supplier_invoice_id', $supplierInvoiceId)
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get([
                'id',
                'supplier_invoice_id',
                'amount_rupiah',
                'paid_at',
                'proof_status',
                'proof_storage_path',
            ])
            ->map(fn (object $row): SupplierPayment => $this->mapRowToPayment($row))
            ->all();
    }

    private function mapRowToPayment(object $row): SupplierPayment
    {
        return SupplierPayment::rehydrate(
            (string) $row->id,
            (string) $row->supplier_invoice_id,
            Money::fromInt((int) $row->amount_rupiah),
            new DateTimeImmutable((string) $row->paid_at),
            (string) $row->proof_status,
            $row->proof_storage_path !== null ? (string) $row->proof_storage_path : null,
        );
    }
}
