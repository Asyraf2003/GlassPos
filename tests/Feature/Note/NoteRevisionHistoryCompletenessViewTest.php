<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Tests\TestCase;

final class NoteRevisionHistoryCompletenessViewTest extends TestCase
{
    public function test_operational_history_keeps_revisions_older_than_three_entries(): void
    {
        $timeline = [];
        foreach ([4, 3, 2, 1] as $number) {
            $timeline[] = [
                'revision_number' => $number,
                'reason' => 'Historical reason '.$number,
                'line_snapshot_rows' => [[
                    'line_no' => 1,
                    'label' => 'Historical service '.$number,
                    'subtotal_rupiah' => $number * 10000,
                ]],
            ];
        }

        $html = view('shared.notes.partials.history-operational', ['note' => [
            'revision_timeline' => ['current' => ['revision_number' => 5], 'timeline' => $timeline],
            'correction_history' => [],
        ]])->render();

        foreach ([4, 3, 2, 1] as $number) {
            self::assertStringContainsString('Historical reason '.$number, $html);
            self::assertStringContainsString('Historical service '.$number, $html);
        }
    }

    public function test_active_revision_reason_is_visible_and_escaped_even_without_change_summary(): void
    {
        $reason = 'Correction <script>alert("reason")</script>';
        $html = view('shared.notes.partials.versioning-compact', [
            'currentRevision' => ['revision_number' => 2, 'reason' => $reason],
            'timelineRevisions' => [],
        ])->render();

        self::assertStringContainsString('Alasan:', $html);
        self::assertStringContainsString(e($reason), $html);
        self::assertStringNotContainsString($reason, $html);
    }
}
