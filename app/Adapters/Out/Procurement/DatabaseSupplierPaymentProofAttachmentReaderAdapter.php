<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\SupplierPaymentProofAttachment\SupplierPaymentProofAttachment;
use App\Ports\Out\Procurement\SupplierPaymentProofAttachmentReaderPort;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class DatabaseSupplierPaymentProofAttachmentReaderAdapter implements SupplierPaymentProofAttachmentReaderPort
{
    public function getById(string $attachmentId): ?SupplierPaymentProofAttachment
    {
        $row = DB::table('supplier_payment_proof_attachments')
            ->where('id', $attachmentId)
            ->first([
                'id',
                'supplier_payment_id',
                'storage_path',
                'original_filename',
                'mime_type',
                'file_size_bytes',
                'uploaded_at',
                'uploaded_by_actor_id',
            ]);

        if ($row === null) {
            return null;
        }

        if (! LaravelSupplierPaymentProofFileStorageAdapter::isValidPath((string) $row->storage_path)) {
            return null;
        }

        return $this->mapRowToAttachment($row);
    }

    public function listBySupplierPaymentId(string $supplierPaymentId): array
    {
        return DB::table('supplier_payment_proof_attachments')
            ->where('supplier_payment_id', $supplierPaymentId)
            ->orderBy('uploaded_at')
            ->orderBy('id')
            ->get([
                'id',
                'supplier_payment_id',
                'storage_path',
                'original_filename',
                'mime_type',
                'file_size_bytes',
                'uploaded_at',
                'uploaded_by_actor_id',
            ])
            ->map(fn (object $row): SupplierPaymentProofAttachment => $this->mapRowToAttachment($row))
            ->all();
    }

    private function mapRowToAttachment(object $row): SupplierPaymentProofAttachment
    {
        return SupplierPaymentProofAttachment::rehydrate(
            (string) $row->id,
            (string) $row->supplier_payment_id,
            (string) $row->storage_path,
            (string) $row->original_filename,
            (string) $row->mime_type,
            (int) $row->file_size_bytes,
            new DateTimeImmutable((string) $row->uploaded_at),
            (string) $row->uploaded_by_actor_id,
        );
    }
}
