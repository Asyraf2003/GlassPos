<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Tests\TestCase;

final class CashierNotePresentationDensityContractTest extends TestCase
{
    public function test_workspace_removes_repeated_section_context_and_duplicate_back_action(): void
    {
        $info = $this->view('cashier/notes/workspace/partials/info-card.blade.php');
        $description = $this->view('cashier/notes/workspace/partials/note-description-card.blade.php');
        $lines = $this->view('cashier/notes/workspace/partials/rincian-card.blade.php');
        $review = $this->view('cashier/notes/workspace/partials/review-payment-card.blade.php');
        $layout = $this->view('layouts/app.blade.php');

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
        $modal = $this->view('cashier/notes/workspace/partials/payment-modal.blade.php');
        $left = $this->view('cashier/notes/workspace/partials/payment-modal-left.blade.php');
        $right = $this->view('cashier/notes/workspace/partials/payment-modal-right.blade.php');
        $cash = $this->view('cashier/notes/workspace/partials/payment-modal-cash.blade.php');

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

    public function test_note_detail_removes_visible_duplicate_summary_copy_and_keeps_history_in_main_desktop_column(): void
    {
        $detail = $this->view('shared/notes/show.blade.php');
        $header = $this->view('shared/notes/partials/header-summary.blade.php');
        $payment = $this->view('shared/notes/partials/payment-summary-actions.blade.php');
        $timeline = $this->view('shared/notes/partials/payment-timeline.blade.php');
        $paymentModal = $this->view('cashier/notes/partials/payment-modal.blade.php');
        $deviceCss = $this->asset('assets/static/css/cashier-note-device-presentation.css');

        self::assertStringContainsString('note-detail-mobile-help', $detail);
        self::assertStringContainsString('body[data-note-device] .note-detail-mobile-help', $deviceCss);
        self::assertStringContainsString("{{ \$note['id'] }}", $header);
        self::assertStringNotContainsString('Jumlah Rincian', $header);
        self::assertStringNotContainsString('Ringkasan Rincian', $header);
        self::assertStringNotContainsString('Status & Aksi Nota', $payment);
        self::assertStringNotContainsString('Status Operasional', $payment);
        self::assertStringNotContainsString('Setiap penerimaan uang dicatat sebagai transaksi terpisah.', $timeline);
        self::assertStringContainsString('<div class="visually-hidden">Kalkulator Tunai</div>', $paymentModal);
        self::assertStringNotContainsString('Hanya tiga angka utama. Angka tengah langsung bisa diisi.', $paymentModal);
        self::assertStringNotContainsString('Tagihan aktif dipilih otomatis. Rincian tagihan dikirim otomatis agar pembayaran tercatat sesuai urutan.', $paymentModal);
        self::assertStringNotContainsString('grid-column: 1 / -1;', $deviceCss);
        self::assertStringContainsString('.note-detail-mobile-step:nth-child(4)', $deviceCss);
        self::assertStringContainsString('grid-row: 1 / span 3;', $deviceCss);
    }

    private function view(string $path): string
    {
        return (string) file_get_contents(resource_path('views/'.$path));
    }

    private function asset(string $path): string
    {
        return (string) file_get_contents(public_path($path));
    }
}
