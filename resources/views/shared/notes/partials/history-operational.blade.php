<div class="note-detail-history-stack note-detail-history-stack--operational">
  @include('shared.notes.partials.versioning-compact', [
    'currentRevision' => ($note['revision_timeline']['current'] ?? []),
    'timelineRevisions' => array_slice(($note['revision_timeline']['timeline'] ?? []), 0, 3),
    'revisionCount' => count($note['revision_timeline']['timeline'] ?? []),
  ])

  @include('cashier.notes.partials.correction-history')
</div>
