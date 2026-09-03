<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Application\Shared\DTO\Result;
use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class PrepareSupplierPaymentProofDirectUploadContractFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    private const HANDLER = 'App\\Application\\Procurement\\UseCases\\PrepareSupplierPaymentProofDirectUploadHandler';

    private FakeSupplierPaymentProofDirectUploadPortForPrepareContract $directUploads;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directUploads = new FakeSupplierPaymentProofDirectUploadPortForPrepareContract();
        $this->app->instance(SupplierPaymentProofDirectUploadPort::class, $this->directUploads);
    }

    public function test_prepare_rejects_missing_actor_without_creating_intent(): void
    {
        $this->seedPaymentFixture('payment-prepare-actor-1', 'invoice-prepare-actor-1');

        $result = $this->prepare(
            'supplier_payment',
            'payment-prepare-actor-1',
            [$this->file('proof.pdf', 'application/pdf', 1024)],
            '',
            'idem-prepare-actor-1',
        );

        self::assertTrue($result->isFailure());
        self::assertSame(0, $this->intentCount());
        self::assertSame(0, $this->directUploads->callCount);
    }

    public function test_prepare_rejects_more_than_three_files_before_presign(): void
    {
        $this->seedPaymentFixture('payment-prepare-count-1', 'invoice-prepare-count-1');

        $files = [
            $this->file('a.pdf', 'application/pdf', 100),
            $this->file('b.pdf', 'application/pdf', 100),
            $this->file('c.pdf', 'application/pdf', 100),
            $this->file('d.pdf', 'application/pdf', 100),
        ];

        $result = $this->prepare(
            'supplier_payment',
            'payment-prepare-count-1',
            $files,
            'actor-prepare-count-1',
            'idem-prepare-count-1',
        );

        self::assertTrue($result->isFailure());
        self::assertSame(0, $this->intentCount());
        self::assertSame(0, $this->directUploads->callCount);
    }

    public function test_prepare_rejects_file_larger_than_ten_mib_before_presign(): void
    {
        $this->seedPaymentFixture('payment-prepare-size-1', 'invoice-prepare-size-1');

        $result = $this->prepare(
            'supplier_payment',
            'payment-prepare-size-1',
            [$this->file('oversized.pdf', 'application/pdf', 10_485_761)],
            'actor-prepare-size-1',
            'idem-prepare-size-1',
        );

        self::assertTrue($result->isFailure());
        self::assertSame(0, $this->intentCount());
        self::assertSame(0, $this->directUploads->callCount);
    }

    public function test_prepare_rejects_unsupported_declared_mime_before_presign(): void
    {
        $this->seedPaymentFixture('payment-prepare-mime-1', 'invoice-prepare-mime-1');

        $result = $this->prepare(
            'supplier_payment',
            'payment-prepare-mime-1',
            [$this->file('proof.txt', 'text/plain', 256)],
            'actor-prepare-mime-1',
            'idem-prepare-mime-1',
        );

        self::assertTrue($result->isFailure());
        self::assertSame(0, $this->intentCount());
        self::assertSame(0, $this->directUploads->callCount);
    }

    public function test_prepare_invoice_reserves_payment_id_without_creating_financial_payment(): void
    {
        $this->seedInvoiceFixture('invoice-prepare-reserve-1', 100000);

        $result = $this->prepare(
            'supplier_invoice',
            'invoice-prepare-reserve-1',
            [$this->file('invoice-proof.pdf', 'application/pdf', 2048)],
            'actor-prepare-reserve-1',
            'idem-prepare-reserve-1',
        );

        self::assertTrue($result->isSuccess());

        $intent = DB::table('supplier_payment_proof_upload_intents')
            ->where('actor_id', 'actor-prepare-reserve-1')
            ->where('scope_type', 'supplier_invoice')
            ->where('scope_id', 'invoice-prepare-reserve-1')
            ->first();

        self::assertNotNull($intent);
        self::assertSame('prepared', (string) $intent->status);
        self::assertNotSame('', trim((string) $intent->reserved_supplier_payment_id));
        self::assertSame(
            0,
            DB::table('supplier_payments')
                ->where('id', (string) $intent->reserved_supplier_payment_id)
                ->count()
        );
        self::assertSame(1, $this->intentFileCount((string) $intent->id));
        self::assertSame(1, $this->directUploads->callCount);
    }

    public function test_prepare_same_actor_scope_key_and_payload_reuses_same_intent(): void
    {
        $this->seedPaymentFixture('payment-prepare-replay-1', 'invoice-prepare-replay-1');
        $files = [$this->file('replay.pdf', 'application/pdf', 4096)];

        $first = $this->prepare(
            'supplier_payment',
            'payment-prepare-replay-1',
            $files,
            'actor-prepare-replay-1',
            'idem-prepare-replay-1',
        );
        $second = $this->prepare(
            'supplier_payment',
            'payment-prepare-replay-1',
            $files,
            'actor-prepare-replay-1',
            'idem-prepare-replay-1',
        );

        self::assertTrue($first->isSuccess());
        self::assertTrue($second->isSuccess());

        $firstData = $this->resultData($first);
        $secondData = $this->resultData($second);

        self::assertSame($firstData['upload_intent_id'] ?? null, $secondData['upload_intent_id'] ?? null);
        self::assertSame(1, $this->intentCount());

        $intentId = (string) ($firstData['upload_intent_id'] ?? '');
        self::assertNotSame('', $intentId);
        self::assertSame(1, $this->intentFileCount($intentId));
        self::assertSame(2, $this->directUploads->callCount, 'Replay prepare may re-presign the same persisted staging paths.');
        self::assertSame($this->directUploads->uploadIntentIds[0] ?? null, $this->directUploads->uploadIntentIds[1] ?? null);
    }

    public function test_prepare_same_key_with_different_payload_is_rejected_without_second_intent(): void
    {
        $this->seedPaymentFixture('payment-prepare-conflict-1', 'invoice-prepare-conflict-1');

        $first = $this->prepare(
            'supplier_payment',
            'payment-prepare-conflict-1',
            [$this->file('proof.pdf', 'application/pdf', 1024)],
            'actor-prepare-conflict-1',
            'idem-prepare-conflict-1',
        );
        $second = $this->prepare(
            'supplier_payment',
            'payment-prepare-conflict-1',
            [$this->file('proof.pdf', 'application/pdf', 2048)],
            'actor-prepare-conflict-1',
            'idem-prepare-conflict-1',
        );

        self::assertTrue($first->isSuccess());
        self::assertTrue($second->isFailure());
        self::assertSame(1, $this->intentCount());
        self::assertSame(1, $this->directUploads->callCount);
        self::assertContains(
            'SUPPLIER_PAYMENT_PROOF_UPLOAD_IDEMPOTENCY_CONFLICT',
            $second->errors()['idempotency_key'] ?? [],
        );
    }

    public function test_prepare_same_key_on_different_actor_does_not_reuse_foreign_intent(): void
    {
        $this->seedPaymentFixture('payment-prepare-actor-scope-1', 'invoice-prepare-actor-scope-1');
        $files = [$this->file('proof.pdf', 'application/pdf', 1024)];

        $first = $this->prepare(
            'supplier_payment',
            'payment-prepare-actor-scope-1',
            $files,
            'actor-prepare-a',
            'idem-shared-key',
        );
        $second = $this->prepare(
            'supplier_payment',
            'payment-prepare-actor-scope-1',
            $files,
            'actor-prepare-b',
            'idem-shared-key',
        );

        self::assertTrue($first->isSuccess());
        self::assertTrue($second->isSuccess());
        self::assertNotSame(
            $this->resultData($first)['upload_intent_id'] ?? null,
            $this->resultData($second)['upload_intent_id'] ?? null,
        );
        self::assertSame(2, $this->intentCount());
    }

    private function prepare(
        string $scopeType,
        string $scopeId,
        array $files,
        string $actorId,
        string $idempotencyKey,
    ): Result {
        self::assertTrue(class_exists(self::HANDLER), self::HANDLER.' must exist for the direct-upload application contract.');

        $handler = $this->app->make(self::HANDLER);
        self::assertTrue(method_exists($handler, 'handle'), self::HANDLER.'::handle() must exist.');

        $result = call_user_func(
            [$handler, 'handle'],
            $scopeType,
            $scopeId,
            $files,
            $actorId,
            $idempotencyKey,
        );

        self::assertInstanceOf(Result::class, $result);

        return $result;
    }

    /** @return array<string,mixed> */
    private function resultData(Result $result): array
    {
        $data = $result->data();
        self::assertIsArray($data);

        return $data;
    }

    /** @return array{original_filename:string,mime_type:string,file_size_bytes:int} */
    private function file(string $name, string $mimeType, int $size): array
    {
        return [
            'original_filename' => $name,
            'mime_type' => $mimeType,
            'file_size_bytes' => $size,
        ];
    }

    private function intentCount(): int
    {
        return (int) DB::table('supplier_payment_proof_upload_intents')->count();
    }

    private function intentFileCount(string $intentId): int
    {
        return (int) DB::table('supplier_payment_proof_upload_intent_files')
            ->where('upload_intent_id', $intentId)
            ->count();
    }

    private function seedPaymentFixture(string $paymentId, string $invoiceId): void
    {
        $this->seedInvoiceFixture($invoiceId, 100000);
        $this->seedMinimalSupplierPayment(
            $paymentId,
            $invoiceId,
            30000,
            '2026-09-03',
            'pending',
            null,
        );
    }

    private function seedInvoiceFixture(string $invoiceId, int $grandTotalRupiah): void
    {
        $suffix = str_replace('_', '-', $invoiceId);

        $this->seedMinimalSupplier(
            'supplier-'.$suffix,
            'PT Supplier Direct Upload',
            'pt supplier direct upload',
        );
        $this->seedMinimalProduct(
            'product-'.$suffix,
            'DU-001',
            'Produk Direct Upload',
            'Brand DU',
            100,
            75000,
        );
        $this->seedMinimalSupplierInvoice(
            $invoiceId,
            'supplier-'.$suffix,
            '2026-09-01',
            '2026-09-30',
            $grandTotalRupiah,
            'PT Supplier Direct Upload',
        );
        $this->seedMinimalSupplierInvoiceLine(
            'invoice-line-'.$suffix,
            $invoiceId,
            'product-'.$suffix,
            2,
            $grandTotalRupiah,
            intdiv($grandTotalRupiah, 2),
            'DU-001',
            'Produk Direct Upload',
            'Brand DU',
            100,
        );
    }
}

final class FakeSupplierPaymentProofDirectUploadPortForPrepareContract implements SupplierPaymentProofDirectUploadPort
{
    public int $callCount = 0;

    /** @var list<string> */
    public array $uploadIntentIds = [];

    public function prepareMany(string $supplierPaymentId, array $files, int $expiresInSeconds = 900): array
    {
        $this->callCount++;
        $uploadIntentId = trim($supplierPaymentId);
        $this->uploadIntentIds[] = $uploadIntentId;

        $prepared = [];

        foreach (array_values($files) as $index => $file) {
            $prepared[] = [
                'storage_path' => sprintf(
                    'supplier-payment-proof-uploads/%s/file-%d.upload',
                    $uploadIntentId,
                    $index + 1,
                ),
                'original_filename' => (string) ($file['original_filename'] ?? ''),
                'mime_type' => (string) ($file['mime_type'] ?? ''),
                'file_size_bytes' => (int) ($file['file_size_bytes'] ?? 0),
                'upload_url' => sprintf('https://private-r2.example.test/%s/%d', $uploadIntentId, $index + 1),
                'headers' => [
                    'Content-Type' => (string) ($file['mime_type'] ?? ''),
                ],
            ];
        }

        return $prepared;
    }
}
