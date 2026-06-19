<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement;

use App\Application\Procurement\UseCases\UploadSupplierInvoicePaymentProofHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;

final class UploadSupplierInvoicePaymentProofController extends Controller
{
    public function __invoke(
        Request $request,
        UploadSupplierInvoicePaymentProofHandler $useCase,
        string $supplierInvoiceId,
    ): RedirectResponse {
        $data = $request->validate([
            'proof_files' => ['required', 'array', 'min:1', 'max:3'],
            'proof_files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $user = $request->user();
        $actorId = $user?->getAuthIdentifier();
        $uploadedFiles = [];
        $proofFiles = is_array($data['proof_files'] ?? null) ? $data['proof_files'] : [];

        foreach ($proofFiles as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $sourcePath = $file->getRealPath();

            if (! is_string($sourcePath) || $sourcePath === '') {
                return back()
                    ->withErrors([
                        'supplier_payment_proof' => 'Bukti pembayaran supplier gagal diunggah.',
                    ])
                    ->withInput();
            }

            $uploadedFiles[] = [
                'source_path' => $sourcePath,
                'original_filename' => (string) $file->getClientOriginalName(),
                'mime_type' => (string) $file->getClientMimeType(),
                'file_size_bytes' => (int) $file->getSize(),
            ];
        }

        $result = $useCase->handle(
            $supplierInvoiceId,
            $uploadedFiles,
            $actorId !== null ? (string) $actorId : '',
        );

        if ($result->isFailure()) {
            return back()
                ->withErrors([
                    'supplier_payment_proof' => $result->message() ?? 'Bukti pembayaran supplier gagal diunggah.',
                ])
                ->withInput();
        }

        return back()->with('success', $result->message() ?? 'Bukti pembayaran supplier berhasil diunggah.');
    }
}
