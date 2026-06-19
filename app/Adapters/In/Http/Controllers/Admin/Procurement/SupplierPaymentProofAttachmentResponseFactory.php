<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement;

use App\Application\Procurement\DTO\SupplierPaymentProofAttachmentFile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class SupplierPaymentProofAttachmentResponseFactory
{
    /**
     * @var array<string, true>
     */
    private const SAFE_MIME_TYPES = [
        'application/pdf' => true,
        'image/jpeg' => true,
        'image/png' => true,
        'image/webp' => true,
        'image/heic' => true,
        'image/heif' => true,
    ];

    /**
     * @var array<string, true>
     */
    private const INLINE_MIME_TYPES = [
        'application/pdf' => true,
        'image/jpeg' => true,
        'image/png' => true,
        'image/webp' => true,
    ];

    public function make(Request $request, SupplierPaymentProofAttachmentFile $file): Response
    {
        $content = $file->content();
        $mimeType = $this->safeMimeType($content);
        $disposition = $request->boolean('download') || ! isset(self::INLINE_MIME_TYPES[$mimeType])
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
            : ResponseHeaderBag::DISPOSITION_INLINE;

        $response = response($content, 200, [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                $disposition,
                $this->safeFilename($file->originalFilename()),
            ),
        );

        return $response;
    }

    private function safeMimeType(string $content): string
    {
        $detectedMimeType = $this->detectMimeType($content);

        return isset(self::SAFE_MIME_TYPES[$detectedMimeType])
            ? $detectedMimeType
            : 'application/octet-stream';
    }

    private function detectMimeType(string $content): string
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMimeType = $fileInfo->buffer($content);

        if (! is_string($detectedMimeType)) {
            return 'application/octet-stream';
        }

        return strtolower(trim($detectedMimeType));
    }

    private function safeFilename(string $filename): string
    {
        $basename = basename(str_replace(["\\", "\0"], '/', $filename));
        $basename = preg_replace('/[\x00-\x1F\x7F"\\\\\/]+/', '-', $basename) ?? '';
        $basename = trim($basename, " .-\t\n\r\0\x0B");

        if ($basename === '') {
            return 'supplier-payment-proof';
        }

        $safeFilename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $basename) ?? '';
        $safeFilename = trim($safeFilename, '.-');

        return $safeFilename !== '' ? $safeFilename : 'supplier-payment-proof';
    }
}
