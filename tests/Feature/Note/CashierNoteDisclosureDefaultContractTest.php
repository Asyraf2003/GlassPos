<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Tests\TestCase;

final class CashierNoteDisclosureDefaultContractTest extends TestCase
{
    public function test_only_lines_and_payment_are_open_by_default(): void
    {
        $detail = (string) file_get_contents(resource_path('views/shared/notes/show.blade.php'));

        self::assertStringContainsString(
            '<details class="note-detail-desktop-panel" data-note-desktop-panel="lines" open>',
            $detail,
        );
        self::assertStringContainsString(
            '<details class="note-detail-desktop-panel" data-note-desktop-panel="payment" open>',
            $detail,
        );

        self::assertStringNotContainsString(
            '<details class="note-detail-desktop-panel" data-note-desktop-panel="info" open>',
            $detail,
        );
        self::assertStringNotContainsString(
            '<details class="note-detail-desktop-panel" data-note-desktop-panel="history-main" open>',
            $detail,
        );
        self::assertStringNotContainsString(
            '<details class="note-detail-desktop-panel" data-note-desktop-panel="history-finance" open>',
            $detail,
        );
        self::assertStringNotContainsString(
            '<details class="note-detail-desktop-panel" data-note-desktop-panel="lifecycle" open>',
            $detail,
        );

        self::assertSame(2, substr_count($detail, '<details class="note-detail-mobile-step" open>'));
    }
}
