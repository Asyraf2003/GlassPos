<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Tests\TestCase;

final class NoteStatusBadgePresentationFeatureTest extends TestCase
{
    public function test_badge_preserves_label_normalization_tone_precedence_and_explicit_override(): void
    {
        foreach ([
            [null, null, '-', 'info'],
            ['   ', null, '-', 'info'],
            [' OPEN ', null, 'OPEN', 'info'],
            ['CANCELED', null, 'CANCELED', 'danger'],
            ['Dibatalkan', null, 'Dibatalkan', 'danger'],
            ['paid-refunded', null, 'paid-refunded', 'danger'],
            ['uang_kembali', null, 'uang_kembali', 'danger'],
            ['Closed', null, 'Closed', 'success'],
            ['Lunas', null, 'Lunas', 'success'],
            ['paid', null, 'paid', 'success'],
            ['Selesai', null, 'Selesai', 'success'],
            ['refund', 'info', 'refund', 'info'],
            ['paid', '', 'paid', ''],
            ['<script>bad</script>', null, '&lt;script&gt;bad&lt;/script&gt;', 'info'],
        ] as [$label, $tone, $text, $expectedTone]) {
            $html = view('shared.notes.partials.status-badge', compact('label', 'tone'))->render();
            self::assertStringContainsString('note-detail-status-badge--'.$expectedTone.'"', $html);
            self::assertStringContainsString($text, $html);
            self::assertStringContainsString('color: #fff !important;', $html);
        }
    }
}
