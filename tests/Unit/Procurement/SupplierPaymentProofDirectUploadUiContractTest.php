<?php

declare(strict_types=1);

namespace Tests\Unit\Procurement;

use PHPUnit\Framework\TestCase;

final class SupplierPaymentProofDirectUploadUiContractTest extends TestCase
{
    public function test_browser_script_prepares_puts_to_presigned_url_then_finalizes_without_multipart(): void
    {
        $script = $this->script();
        $prepare = strpos($script, 'postJson(form.dataset.prepareUrl');
        $put = strpos($script, "method: 'PUT'");
        $finalize = strpos($script, 'postJson(finalizeUrl');

        self::assertIsInt($prepare);
        self::assertIsInt($put);
        self::assertIsInt($finalize);
        self::assertLessThan($put, $prepare);
        self::assertLessThan($finalize, $put);
        self::assertStringContainsString('body: files[index]', $script);
        self::assertStringContainsString('body: JSON.stringify(body)', $script);
        self::assertStringContainsString("postJson(finalizeUrl, {}, 'finalize')", $script);
        self::assertStringNotContainsString('FormData', $script);
        self::assertStringNotContainsString('multipart/form-data', $script);
    }

    public function test_native_fetch_and_camera_errors_are_mapped_and_never_exposed_to_the_user(): void
    {
        $script = $this->script();

        self::assertStringContainsString("throw failure('appNetworkUnavailable')", $script);
        self::assertStringContainsString("throw failure('storageNetworkUnavailable')", $script);
        self::assertStringContainsString("throw failure('storageUploadRejected')", $script);
        self::assertStringContainsString('setState(form, publicMessageFor(error), true)', $script);
        self::assertStringContainsString("name === 'NotAllowedError'", $script);
        self::assertStringContainsString("name === 'NotFoundError'", $script);
        self::assertStringContainsString("name === 'NotReadableError'", $script);
        self::assertStringNotContainsString('error.message', $script);
        self::assertStringNotContainsString('Failed to fetch', $script);
        self::assertStringNotContainsString('instanceof Error', $script);
    }

    public function test_browser_visible_failures_come_from_the_trusted_indonesian_mapping(): void
    {
        $script = $this->script();
        $expected = [
            'Aplikasi tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
            'Penyimpanan privat tidak dapat dihubungi. Periksa koneksi lalu coba lagi.',
            'Penyimpanan privat menolak upload. Silakan coba lagi.',
            'Respons persiapan upload tidak valid. Silakan coba lagi.',
            'Upload bukti pembayaran gagal disiapkan.',
            'Upload bukti pembayaran gagal difinalisasi.',
            'Pilih 1 sampai 3 file bukti pembayaran.',
            'Ukuran tiap bukti pembayaran harus lebih dari 0 dan maksimal 10 MB.',
            'Format bukti harus JPG, PNG, WEBP, HEIC, HEIF, atau PDF.',
            'Scope bukti pembayaran tidak tersedia.',
            'Kamera tidak didukung oleh browser ini. Gunakan pilihan file atau galeri.',
            'Izin kamera ditolak. Izinkan akses kamera atau gunakan pilihan file.',
            'Kamera tidak ditemukan pada perangkat ini.',
            'Kamera sedang tidak dapat digunakan. Tutup aplikasi lain yang memakai kamera lalu coba lagi.',
            'Foto dari kamera gagal diambil. Silakan coba lagi.',
            'Bukti pembayaran gagal diproses. Silakan coba lagi.',
        ];
        preg_match(
            '/const publicMessages = Object\.freeze\(\{(?<messages>.*?)\}\);/s',
            $script,
            $mapping,
        );
        preg_match_all("/: '([^']+)'/", (string) ($mapping['messages'] ?? ''), $messages);

        self::assertSame($expected, $messages[1]);
        self::assertStringContainsString('if (!(error instanceof DirectUploadFailure))', $script);
        self::assertStringContainsString('return publicMessages.unknown;', $script);
        self::assertStringContainsString('trustedBackendMessages.has(backendMessage)', $script);
    }

    public function test_camera_files_share_the_same_upload_pipeline_as_picker_files(): void
    {
        $script = $this->script();

        self::assertStringContainsString('navigator.mediaDevices.getUserMedia', $script);
        self::assertStringContainsString('new File([blob], `bukti-kamera-${Date.now()}.jpg`', $script);
        self::assertStringContainsString("type: 'image/jpeg'", $script);
        self::assertStringContainsString('const files = selectedFiles(form);', $script);
        self::assertStringContainsString('...Array.from(input?.files || [])', $script);
        self::assertStringContainsString('...cameraState(form).files', $script);
        self::assertStringContainsString('input.required = false;', $script);
        self::assertStringContainsString('resetIdempotency(form);', $script);
        self::assertStringContainsString('state.stream?.getTracks?.().forEach((track) => track.stop())', $script);
        self::assertStringContainsString("window.addEventListener('pagehide'", $script);
    }

    public function test_action_modal_handoff_waits_for_bootstrap_hidden_event_before_reopening_target_modal(): void
    {
        $script = $this->script();
        $hide = strpos($script, "actionModal.addEventListener('hide.bs.modal'");
        $hidden = strpos($script, "actionModal.addEventListener('hidden.bs.modal'");
        $showGuard = strpos($script, "target.addEventListener('show.bs.modal'");
        $reopen = strpos($script, 'window.bootstrap.Modal.getOrCreateInstance(target).show()');

        self::assertIsInt($hide);
        self::assertIsInt($hidden);
        self::assertIsInt($showGuard);
        self::assertIsInt($reopen);
        self::assertStringContainsString('if (!actionHiding) return;', $script);
        self::assertStringContainsString('event.preventDefault();', $script);
        self::assertStringContainsString('pendingTarget = target;', $script);
        self::assertStringNotContainsString('setTimeout(() => window.bootstrap.Modal', $script);
    }

    public function test_failed_storage_put_stops_before_finalize_and_malformed_prepare_is_distinct(): void
    {
        $script = $this->script();
        $putFailure = strpos($script, "throw failure('storageNetworkUnavailable')");
        $finalize = strpos($script, "postJson(finalizeUrl, {}, 'finalize')");

        self::assertIsInt($putFailure);
        self::assertIsInt($finalize);
        self::assertLessThan($finalize, $putFailure);
        self::assertStringContainsString("throw failure('malformedPreparation')", $script);
        self::assertStringContainsString(
            "throw failure(stage === 'prepare' ? 'malformedPreparation' : 'finalizeFailed')",
            $script,
        );
        self::assertStringContainsString('return headers.length === 0 ? {} : null;', $script);
        self::assertStringContainsString('headers: browserHeaders(prepared.files[index].headers)', $script);
    }

    private function script(): string
    {
        $script = file_get_contents(dirname(__DIR__, 3)
            .'/public/assets/static/js/shared/supplier-payment-proof-direct-upload.js');
        self::assertIsString($script);

        return $script;
    }
}
