<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement;

use App\Application\Procurement\UseCases\GetSupplierPaymentProofAttachmentFileHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class ServeSupplierPaymentProofAttachmentController extends Controller
{
    public function __invoke(
        Request $request,
        GetSupplierPaymentProofAttachmentFileHandler $handler,
        SupplierPaymentProofAttachmentResponseFactory $responses,
        string $attachmentId,
    ): Response {
        $file = $handler->handle($attachmentId);

        abort_if($file === null, 404);

        return $responses->make($request, $file);
    }
}
