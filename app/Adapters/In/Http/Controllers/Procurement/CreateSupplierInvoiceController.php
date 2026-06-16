<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Procurement;

use App\Adapters\In\Http\Presenters\JsonPresenter;
use App\Adapters\In\Http\Requests\Procurement\CreateSupplierInvoiceRequest;
use App\Application\Procurement\UseCases\CreateSupplierInvoiceFlowHandler;
use Illuminate\Http\JsonResponse;

final class CreateSupplierInvoiceController
{
    public function __invoke(
        CreateSupplierInvoiceRequest $request,
        CreateSupplierInvoiceFlowHandler $useCase,
        JsonPresenter $presenter,
    ): JsonResponse {
        $data = $request->validated();

        $result = $useCase->handle(
            (string) $data['nomor_faktur'],
            (string) $data['nama_pt_pengirim'],
            (string) $data['tanggal_pengiriman'],
            $data['lines'],
            $this->resolveAutoReceive($data),
            $this->resolveTanggalTerima($data),
            taxInput: $this->resolveTaxInput($data),
        );

        if ($result->isFailure()) {
            return $presenter->failure($result);
        }

        return $presenter->success($result);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveTaxInput(array $data): ?string
    {
        if (! array_key_exists('tax_input', $data) || $data['tax_input'] === null) {
            return null;
        }

        $value = trim((string) $data['tax_input']);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveAutoReceive(array $data): bool
    {
        if (! array_key_exists('auto_receive', $data) || $data['auto_receive'] === null) {
            return true;
        }

        return (bool) $data['auto_receive'];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveTanggalTerima(array $data): ?string
    {
        if (! array_key_exists('tanggal_terima', $data) || $data['tanggal_terima'] === null) {
            return null;
        }

        return (string) $data['tanggal_terima'];
    }
}
