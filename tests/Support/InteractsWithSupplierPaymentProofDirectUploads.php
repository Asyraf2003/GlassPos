<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

trait InteractsWithSupplierPaymentProofDirectUploads
{
    protected function fakeSupplierPaymentProofDirectUploads(): void
    {
        Storage::fake('r2_private');
        $this->app->instance(
            SupplierPaymentProofDirectUploadPort::class,
            new FakeSupplierPaymentProofDirectUploadPort,
        );
    }

    /**
     * @param  list<array{original_filename:string,mime_type:string,contents:string}>  $files
     */
    protected function uploadSupplierPaymentProofDirectly(
        Authenticatable $actor,
        string $scopeType,
        string $scopeId,
        array $files,
        string $idempotencyKey,
    ): TestResponse {
        $prepared = $this->actingAs($actor)->postJson(
            route('admin.procurement.supplier-payment-proofs.direct-upload.prepare'),
            [
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'idempotency_key' => $idempotencyKey,
                'files' => array_map(static fn (array $file): array => [
                    'original_filename' => $file['original_filename'],
                    'mime_type' => $file['mime_type'],
                    'file_size_bytes' => strlen($file['contents']),
                ], $files),
            ],
        );
        $prepared->assertOk()->assertJsonPath('success', true);
        $intentId = (string) $prepared->json('data.upload_intent_id');

        foreach ($files as $index => $file) {
            $path = (string) $prepared->json("data.files.{$index}.storage_path");
            Storage::disk('r2_private')->put($path, $file['contents']);
        }

        return $this->actingAs($actor)->postJson(route(
            'admin.procurement.supplier-payment-proofs.direct-upload.finalize',
            ['uploadIntentId' => $intentId],
        ));
    }

    /** @return array{original_filename:string,mime_type:string,contents:string} */
    protected function directPdf(string $filename = 'proof.pdf'): array
    {
        return [
            'original_filename' => $filename,
            'mime_type' => 'application/pdf',
            'contents' => "%PDF-1.4\n% GlassPos direct upload fixture\n1 0 obj\n<<>>\nendobj\n%%EOF\n",
        ];
    }

    /** @return array{original_filename:string,mime_type:string,contents:string} */
    protected function directWebp(string $filename = 'proof.webp'): array
    {
        $contents = base64_decode(
            'UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEAAUAmJaQAA3AA/v89WAAAAA==',
            true,
        );

        return [
            'original_filename' => $filename,
            'mime_type' => 'image/webp',
            'contents' => is_string($contents) ? $contents : '',
        ];
    }
}
