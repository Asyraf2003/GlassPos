<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Tests\TestCase;

final class CashierNoteDesktopLayoutV2ContractTest extends TestCase
{
    public function test_desktop_detail_uses_two_independent_vertical_stacks(): void
    {
        $view = $this->readViewSource('shared/notes/show.blade.php');

        self::assertStringContainsString('data-note-desktop-stack="main"', $view);
        self::assertStringContainsString('data-note-desktop-stack="finance"', $view);
        self::assertSame(2, substr_count($view, 'data-note-desktop-stack='));

        $mainStart = strpos($view, 'data-note-desktop-stack="main"');
        $financeStart = strpos($view, 'data-note-desktop-stack="finance"');

        self::assertIsInt($mainStart);
        self::assertIsInt($financeStart);
        self::assertLessThan($financeStart, $mainStart);

        $mainStack = substr($view, $mainStart, $financeStart - $mainStart);
        $financeStack = substr($view, $financeStart);

        self::assertStringContainsString('data-note-desktop-panel="info"', $mainStack);
        self::assertStringContainsString('data-note-desktop-panel="lines"', $mainStack);
        self::assertStringContainsString('data-note-desktop-panel="history-main"', $mainStack);
        self::assertStringNotContainsString('data-note-desktop-panel="payment"', $mainStack);
        self::assertStringNotContainsString('data-note-desktop-panel="history-finance"', $mainStack);

        self::assertStringContainsString('data-note-desktop-panel="payment"', $financeStack);
        self::assertStringContainsString('data-note-desktop-panel="history-finance"', $financeStack);
    }

    public function test_desktop_stack_order_makes_lower_panels_rise_when_upper_panels_collapse(): void
    {
        $view = $this->readViewSource('shared/notes/show.blade.php');

        $info = strpos($view, 'data-note-desktop-panel="info"');
        $lines = strpos($view, 'data-note-desktop-panel="lines"');
        $historyMain = strpos($view, 'data-note-desktop-panel="history-main"');
        $payment = strpos($view, 'data-note-desktop-panel="payment"');
        $historyFinance = strpos($view, 'data-note-desktop-panel="history-finance"');

        self::assertIsInt($info);
        self::assertIsInt($lines);
        self::assertIsInt($historyMain);
        self::assertIsInt($payment);
        self::assertIsInt($historyFinance);

        self::assertLessThan($lines, $info);
        self::assertLessThan($historyMain, $lines);
        self::assertLessThan($historyFinance, $payment);
        self::assertGreaterThanOrEqual(5, substr_count($view, '<details'));
    }

    public function test_desktop_css_uses_two_column_shell_with_independent_vertical_flow(): void
    {
        $css = $this->readPublicAsset('assets/static/css/note-detail-desktop-polish.css');

        self::assertStringContainsString('.note-detail-desktop-columns', $css);
        self::assertStringContainsString('grid-template-columns:', $css);
        self::assertStringContainsString('.note-detail-desktop-stack', $css);
        self::assertStringContainsString('display: flex;', $css);
        self::assertStringContainsString('flex-direction: column;', $css);
        self::assertStringContainsString('align-self: start;', $css);

        self::assertStringNotContainsString('grid-template-areas:', $css);
        self::assertStringNotContainsString('grid-area:', $css);
        self::assertStringNotContainsString('grid-row: 1 / span', $css);
    }

    public function test_desktop_detail_splits_operational_history_from_financial_history(): void
    {
        $view = $this->readViewSource('shared/notes/show.blade.php');

        self::assertStringContainsString("@include('shared.notes.partials.history-operational')", $view);
        self::assertStringContainsString("@include('shared.notes.partials.history-financial')", $view);
        self::assertSame(1, substr_count($view, "@include('shared.notes.partials.history-panel')"));
    }

    public function test_handset_keeps_its_compact_stack_instead_of_inheriting_desktop_columns(): void
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
