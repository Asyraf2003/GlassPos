<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Tests\TestCase;

final class CashierNotePresentationDensityContractTest extends TestCase
{
    public function test_workspace_removes_repeated_section_context_and_duplicate_back_action(): void
    {
        $info = $this->readViewSource('cashier/notes/workspace/partials/info-card.blade.php');
        $description = $this->readViewSource('cashier/notes/workspace/partials/note-description-card.blade.php');
        $lines = $this->readViewSource('cashier/notes/workspace/partials/rincian-card.blade.php');
        $review = $this->readViewSource('cashier/notes/workspace/partials/review-payment-card.blade.php');
        $layout = $this->readViewSource('layouts/app.blade.php');

        self::assertStringNotContainsString('workspace-panel-eyebrow', $info);
        self::assertStringNotContainsString('workspace-mode-badge', $info);
        self::assertStringNotContainsString('Pelanggan & tanggal', $info);
        self::assertStringNotContainsString('workspace-panel-heading', $description);
        self::assertStringContainsString('Akan tampil di Riwayat Perubahan Nota.', $description);
        self::assertStringContainsString('visually-hidden', $description);
        self::assertStringNotContainsString('workspace-panel-eyebrow', $lines);
        self::assertStringNotContainsString('Pilihan langsung menambahkan satu rincian ke nota aktif.', $lines);
        self::assertStringNotContainsString('Cari lalu pilih item yang akan masuk ke nota.', $lines);
        self::assertStringNotContainsString('Nota Aktif', $review);
        self::assertStringNotContainsString('workspace-cancel-link', $review);
        self::assertStringNotContainsString('Batal dan kembali', $review);
        self::assertStringContainsString('data-layout-smart-back', $layout);
    }

    public function test_create_and_edit_payment_ui_keeps_financial_hooks_but_hides_explanatory_duplication(): void
    {
        $modal = $this->readViewSource('cashier/notes/workspace/partials/payment-modal.blade.php');
        $left = $this->readViewSource('cashier/notes/workspace/partials/payment-modal-left.blade.php');
        $right = $this->readViewSource('cashier/notes/workspace/partials/payment-modal-right.blade.php');
        $cash = $this->readViewSource('cashier/notes/workspace/partials/payment-modal-cash.blade.php');

        self::assertStringContainsString('id="workspace-payment-modal-subtitle"', $modal);
        self::assertStringContainsString('visually-hidden', $modal);
        self::assertStringNotContainsString('Cek isi transaksi sebelum diproses.', $left);
        self::assertStringNotContainsString('Mode Aktif', $right);
        self::assertStringNotContainsString('Tanggal Pembayaran', $right);
        self::assertStringContainsString('id="workspace-payment-mode-text"', $right);
        self::assertStringContainsString('visually-hidden', $right);
        self::assertStringContainsString('id="workspace-cash-mode-text"', $cash);
        self::assertStringContainsString('<div class="visually-hidden">Kalkulator Tunai</div>', $cash);
        self::assertStringNotContainsString('Hanya tiga angka utama. Angka tengah langsung bisa diisi.', $cash);
        self::assertStringNotContainsString('Ketik nominal, cek kembalian, lalu simpan tunai saat jumlah cukup.', $cash);
    }

    public function test_note_detail_desktop_separates_current_state_from_history_without_losing_business_truth(): void
    {
        $detail = $this->readViewSource('shared/notes/show.blade.php');
        $header = $this->readViewSource('shared/notes/partials/header-summary.blade.php');
        $lines = $this->readViewSource('shared/notes/partials/line-workspace.blade.php');
        $payment = $this->readViewSource('shared/notes/partials/payment-summary-actions.blade.php');
        $history = $this->readViewSource('shared/notes/partials/history-panel.blade.php');
        $timeline = $this->readViewSource('shared/notes/partials/payment-timeline.blade.php');
        $paymentModal = $this->readViewSource('cashier/notes/partials/payment-modal.blade.php');
        $deviceCss = $this->readPublicAsset('assets/static/css/cashier-note-device-presentation.css');

        self::assertStringContainsString('note-detail-desktop', $detail);
        self::assertStringContainsString('note-detail-handset', $detail);
        self::assertStringContainsString("\$noteDetailLayout ?? 'desktop'", $detail);
        self::assertStringContainsString('<h4>Pembayaran</h4>', $detail);
        self::assertStringNotContainsString('note-detail-desktop-history note-detail-surface', $detail);
        self::assertStringContainsString('note-detail-history-title', $detail);
        self::assertStringContainsString("{{ \$note['id'] }}", $header);
        self::assertStringContainsString('Alasan Nota', $header);
        self::assertStringNotContainsString('Jumlah Rincian', $header);
        self::assertStringNotContainsString('Ringkasan Rincian', $header);
        self::assertStringContainsString('line_summary', $lines);
        self::assertStringContainsString('payment_status_label', $payment);
        self::assertStringNotContainsString("@include('shared.notes.partials.payment-timeline')", $payment);
        self::assertStringNotContainsString('Riwayat Pengembalian Otomatis', $payment);
        self::assertStringContainsString("@include('shared.notes.partials.payment-timeline')", $history);
        self::assertStringContainsString('Riwayat Pengembalian Otomatis', $history);
        self::assertStringContainsString('Riwayat Perubahan Nota', $history);
        self::assertStringNotContainsString('Setiap penerimaan uang dicatat sebagai transaksi terpisah.', $timeline);
        self::assertStringContainsString('<div class="visually-hidden">Kalkulator Tunai</div>', $paymentModal);
        self::assertStringNotContainsString('Hanya tiga angka utama. Angka tengah langsung bisa diisi.', $paymentModal);
        self::assertStringNotContainsString('Tagihan aktif dipilih otomatis. Rincian tagihan dikirim otomatis agar pembayaran tercatat sesuai urutan.', $paymentModal);
        self::assertStringContainsString('.note-detail-desktop-main', $deviceCss);
        self::assertStringContainsString('.note-detail-desktop-history', $deviceCss);
        self::assertStringNotContainsString('grid-row: 1 / span 3;', $deviceCss);
        self::assertStringNotContainsString('.note-detail-mobile-step:nth-child(4)', $deviceCss);
    }

    private function readViewSource(string $path): string
    {
        return (string) file_get_contents(resource_path('views/'.$path));
    }

    private function readPublicAsset(string $path): string
    {
        return (string) file_get_contents(public_path($path));
    }
}
