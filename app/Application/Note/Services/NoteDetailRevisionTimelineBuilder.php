<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Revision\NoteRevision;

final class NoteDetailRevisionTimelineBuilder
{
    public function __construct(
        private readonly NoteRevisionTimelineSummaryBuilder $summaries,
        private readonly NoteRevisionTimelineParentResolver $parents,
        private readonly NoteRevisionLineSnapshotViewMapper $lineSnapshots,
    ) {
    }

    /**
     * @param list<NoteRevision> $timeline
     * @return array<string, mixed>
     */
    public function build(NoteRevision $current, array $timeline): array
    {
        $all = $this->indexRevisions(array_merge([$current], $timeline));
        $baselineRevision = $timeline === [] ? $current : $timeline[count($timeline) - 1];

        return [
            'current' => $this->mapRevisionEntry($current, $all),
            'baseline' => [
                'revision_id' => $baselineRevision->id(),
                'revision_number' => $baselineRevision->revisionNumber(),
                'customer_name' => $baselineRevision->customerName(),
                'customer_phone' => $baselineRevision->customerPhone(),
                'transaction_date' => $baselineRevision->transactionDate()->format('Y-m-d'),
                'grand_total_rupiah' => $baselineRevision->grandTotalRupiah(),
                'line_count' => $baselineRevision->lineCount(),
                'created_at' => $baselineRevision->createdAt()->format('Y-m-d H:i:s'),
                'reason' => $baselineRevision->reason(),
            ],
            'timeline' => array_map(
                fn (NoteRevision $revision): array => $this->mapRevisionEntry($revision, $all),
                $timeline
            ),
        ];
    }

    private function mapRevisionEntry(NoteRevision $revision, array $all): array
    {
        return [
            'revision_id' => $revision->id(),
            'revision_number' => $revision->revisionNumber(),
            'parent_revision_id' => $revision->parentRevisionId(),
            'created_by_actor_id' => $revision->createdByActorId(),
            'reason' => $revision->reason(),
            'customer_name' => $revision->customerName(),
            'customer_phone' => $revision->customerPhone(),
            'transaction_date' => $revision->transactionDate()->format('Y-m-d'),
            'grand_total_rupiah' => $revision->grandTotalRupiah(),
            'line_count' => $revision->lineCount(),
            'created_at' => $revision->createdAt()->format('Y-m-d H:i:s'),
            'change_summary_lines' => $this->summaries->build(
                $revision,
                $this->parents->resolve($revision, $all),
            ),
            'line_snapshot_rows' => $this->lineSnapshots->mapMany($revision->lines()),
        ];
    }

    /**
     * @param list<NoteRevision> $revisions
     * @return array<string, NoteRevision>
     */
    private function indexRevisions(array $revisions): array
    {
        $indexed = [];

        foreach ($revisions as $revision) {
            $indexed[$revision->id()] = $revision;
        }

        return $indexed;
    }
}
