<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait SeedsSupplierPaymentProofMatrixFixture
{
    use SeedsMinimalProcurementFixture;

    private function seedPaymentFixture(
        string $paymentId = 'payment-1',
        string $invoiceId = 'invoice-1',
        string $proofStatus = 'pending'
    ): void {
        $this->seedMinimalSupplier('supplier-1', 'PT Supplier Test', 'pt supplier test');
        $this->seedMinimalProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 75000);

        $this->seedMinimalSupplierInvoice(
            $invoiceId,
            'supplier-1',
            '2026-03-15',
            '2026-04-15',
            100000,
            'PT Supplier Test'
        );

        $this->seedMinimalSupplierInvoiceLine(
            'invoice-line-1',
            $invoiceId,
            'product-1',
            2,
            100000,
            50000,
            'KB-001',
            'Ban Luar',
            'Federal',
            100
        );

        $this->seedMinimalSupplierPayment(
            $paymentId,
            $invoiceId,
            30000,
            '2026-03-20',
            $proofStatus,
            null
        );
    }

    private function seedAttachment(
        string $id,
        string $supplierPaymentId,
        string $storagePath,
        string $originalFilename,
        string $mimeType
    ): void {
        DB::table('supplier_payment_proof_attachments')->insert([
            'id' => $id,
            'supplier_payment_id' => $supplierPaymentId,
            'storage_path' => $storagePath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size_bytes' => 12345,
            'uploaded_at' => '2026-03-20 10:00:00',
            'uploaded_by_actor_id' => 'actor-1',
        ]);
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Proof Matrix',
            'email' => 'admin-proof-matrix-' . uniqid() . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }

    private function threeValidFiles(): array
    {
        return [
            UploadedFile::fake()->create('proof-a.pdf', 120, 'application/pdf'),
            UploadedFile::fake()->image('proof-b.jpg'),
            UploadedFile::fake()->image('proof-c.png'),
        ];
    }

    private function fourValidFiles(): array
    {
        return [
            UploadedFile::fake()->create('proof-a.pdf', 120, 'application/pdf'),
            UploadedFile::fake()->image('proof-b.jpg'),
            UploadedFile::fake()->image('proof-c.png'),
            UploadedFile::fake()->image('proof-d.jpg'),
        ];
    }

    private function fakeStorage(): void
    {
        Storage::fake('r2_private');
    }
}
