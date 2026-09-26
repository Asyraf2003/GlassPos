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

    public function test_note_detail_keeps_business_truth_while_history_is_split_by_responsibility(): void
    {
        $detail = $this->readViewSource('shared/notes/show.blade.php');
        $header = $this->readViewSource('shared/notes/partials/header-summary.blade.php');
        $lines = $this->readViewSource('shared/notes/partials/line-workspace.blade.php');
        $payment = $this->readViewSource('shared/notes/partials/payment-summary-actions.blade.php');
        $operational = $this->readViewSource('shared/notes/partials/history-operational.blade.php');
        $financial = $this->readViewSource('shared/notes/partials/history-financial.blade.php');
        $timeline = $this->readViewSource('shared/notes/partials/payment-timeline.blade.php');
        $paymentModal = $this->readViewSource('cashier/notes/partials/payment-modal.blade.php');

        self::assertStringContainsString('note-detail-desktop', $detail);
        self::assertStringContainsString('note-detail-handset', $detail);
        self::assertStringContainsString("\$noteDetailLayout ?? 'desktop'", $detail);
        self::assertStringContainsString('<h4>Pembayaran</h4>', $detail);
        self::assertStringContainsString("{{ \$note['id'] }}", $header);
        self::assertStringContainsString('Alasan Nota', $header);
        self::assertStringNotContainsString('Jumlah Rincian', $header);
        self::assertStringNotContainsString('Ringkasan Rincian', $header);
        self::assertStringContainsString('line_summary', $lines);
        self::assertStringContainsString('payment_status_label', $payment);
        self::assertStringNotContainsString("@include('shared.notes.partials.payment-timeline')", $payment);
        self::assertStringNotContainsString('Riwayat Pengembalian Otomatis', $payment);
        self::assertStringContainsString("@include('shared.notes.partials.versioning-compact'", $operational);
        self::assertStringContainsString("@include('cashier.notes.partials.correction-history')", $operational);
        self::assertStringContainsString("@include('shared.notes.partials.payment-timeline')", $financial);
        self::assertStringContainsString('Riwayat Pengembalian Otomatis', $financial);
        self::assertStringNotContainsString('Setiap penerimaan uang dicatat sebagai transaksi terpisah.', $timeline);
        self::assertStringContainsString('<div class="visually-hidden">Kalkulator Tunai</div>', $paymentModal);
        self::assertStringNotContainsString('Hanya tiga angka utama. Angka tengah langsung bisa diisi.', $paymentModal);
        self::assertStringNotContainsString('Tagihan aktif dipilih otomatis. Rincian tagihan dikirim otomatis agar pembayaran tercatat sesuai urutan.', $paymentModal);
    }

    public function test_note_detail_uses_canonical_flat_visual_contract(): void
    {
        $detail = $this->readViewSource('shared/notes/show.blade.php');
        $header = $this->readViewSource('shared/notes/partials/header-summary.blade.php');
        $lines = $this->readViewSource('shared/notes/partials/line-workspace.blade.php');
        $versions = $this->readViewSource('shared/notes/partials/versioning-compact.blade.php');
        $corrections = $this->readViewSource('cashier/notes/partials/correction-history.blade.php');
        $payment = $this->readViewSource('shared/notes/partials/payment-summary-actions.blade.php');
        $lifecycle = $this->readViewSource('shared/notes/partials/lifecycle-actions.blade.php');
        $status = $this->readViewSource('shared/notes/partials/status-badge.blade.php');
        $css = (string) file_get_contents(public_path('assets/static/css/note-detail-canonical.css'));

        self::assertStringContainsString('note-detail-canonical.css', $detail);
        self::assertStringContainsString("'label' => \$note['operational_status'] ?? '-'", $detail);
        self::assertStringContainsString("'label' => \$note['payment_status_label'] ?? '-'", $detail);
        self::assertGreaterThan(
            strpos($detail, 'data-note-desktop-panel="history-finance"'),
            strpos($detail, 'data-note-desktop-panel="lifecycle"'),
        );

        self::assertStringContainsString('note-detail-info-list', $header);
        self::assertStringNotContainsString('note-detail-readonly-control', $header);
        self::assertStringNotContainsString('note-detail-readonly-field--status', $header);

        self::assertStringContainsString('note-detail-line-row', $lines);
        self::assertStringNotContainsString('note-detail-line-card', $lines);
        self::assertStringNotContainsString('<style>', $lines);
        self::assertStringContainsString('data-refund-row="1"', $lines);
        self::assertStringContainsString("@include('shared.notes.partials.status-badge'", $lines);

        self::assertStringContainsString('note-detail-version-row--current', $versions);
        self::assertStringContainsString('>v{{', $versions);
        self::assertStringNotContainsString('Belum ada riwayat revisi.', $versions);
        self::assertStringNotContainsString('class="card', $versions);
        self::assertStringNotContainsString('class="card', $corrections);
        self::assertStringNotContainsString('class="card', $payment);
        self::assertStringNotContainsString('class="card', $lifecycle);

        self::assertStringContainsString('data-bs-target="#note-cancel-modal"', $lifecycle);
        self::assertStringContainsString('id="note-cancel-modal"', $lifecycle);
        self::assertStringContainsString('data-bs-target="#note-restore-modal"', $lifecycle);
        self::assertStringContainsString('id="note-restore-modal"', $lifecycle);
        self::assertStringContainsString('type="text"', $lifecycle);
        self::assertStringContainsString('shown.bs.modal', $lifecycle);
        self::assertStringContainsString('Batalkan Transaksi', $lifecycle);
        self::assertStringContainsString('Pulihkan sebagai revisi baru', $lifecycle);
        self::assertStringContainsString('name="base_revision_id"', $lifecycle);
        self::assertStringContainsString('name="idempotency_key"', $lifecycle);

        self::assertStringContainsString('note-detail-status-badge--info', $css);
        self::assertStringContainsString('note-detail-status-badge--success', $css);
        self::assertStringContainsString('note-detail-status-badge--danger', $css);
        self::assertStringContainsString('--note-detail-page-title-size: 1.5rem', $css);
        self::assertStringContainsString('--note-detail-card-title-size: 1.125rem', $css);
        self::assertStringContainsString('--note-detail-content-size: 1rem', $css);
        self::assertStringContainsString('html[data-bs-theme="dark"] body[data-note-device] .note-detail-shell', $css);
        self::assertStringNotContainsString('fs-6', $status);
        self::assertStringContainsString('font-size: .8rem !important;', $status);
        self::assertStringContainsString('min-height: 1.4rem;', $status);
    }

    private function readViewSource(string $path): string
    {
        return (string) file_get_contents(resource_path('views/'.$path));
    }
}
