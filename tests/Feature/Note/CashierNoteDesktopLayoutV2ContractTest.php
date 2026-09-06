<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Tests\TestCase;

final class CashierNoteDesktopLayoutV2ContractTest extends TestCase
{
    public function test_desktop_detail_uses_independent_collapsible_panels_with_fixed_column_roles(): void
    {
        $view = $this->readViewSource('shared/notes/show.blade.php');

        self::assertStringContainsString('data-note-desktop-panel="info"', $view);
        self::assertStringContainsString('data-note-desktop-panel="lines"', $view);
        self::assertStringContainsString('data-note-desktop-panel="payment"', $view);
        self::assertStringContainsString('data-note-desktop-panel="history-main"', $view);
        self::assertStringContainsString('data-note-desktop-panel="history-finance"', $view);
        self::assertGreaterThanOrEqual(5, substr_count($view, '<details'));
    }

    public function test_desktop_detail_splits_operational_history_from_financial_history(): void
    {
        $view = $this->readViewSource('shared/notes/show.blade.php');

        self::assertStringContainsString("@include('shared.notes.partials.history-operational')", $view);
        self::assertStringContainsString("@include('shared.notes.partials.history-financial')", $view);
        self::assertSame(
            1,
            substr_count($view, "@include('shared.notes.partials.history-panel')"),
            'Legacy combined history panel is reserved for the handset path only.',
        );
    }

    public function test_desktop_grid_keeps_left_and_right_columns_symmetric_without_row_spans(): void
    {
        $css = $this->readPublicAsset('assets/static/css/note-detail-desktop-polish.css');

        self::assertStringContainsString('grid-template-areas:', $css);
        self::assertStringContainsString('"info info"', $css);
        self::assertStringContainsString('"lines payment"', $css);
        self::assertStringContainsString('"history-main history-finance"', $css);
        self::assertStringNotContainsString('grid-row: 1 / span', $css);
        self::assertStringNotContainsString('grid-row: 1 / span 3', $css);
    }

    public function test_handset_keeps_its_compact_stack_instead_of_inheriting_desktop_grid(): void
    {
        $view = $this->readViewSource('shared/notes/show.blade.php');

        self::assertStringContainsString('note-detail-mobile-stack', $view);
        self::assertStringContainsString('note-detail-handset', $view);
        self::assertStringContainsString("(\$noteDetailLayout ?? 'desktop') === 'desktop'", $view);
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
