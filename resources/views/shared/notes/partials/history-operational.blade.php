<div class="note-detail-history-stack note-detail-history-stack--operational">
  @include('shared.notes.partials.versioning-compact', [
    'currentRevision' => ($note['revision_timeline']['current'] ?? []),
    'timelineRevisions' => ($note['revision_timeline']['timeline'] ?? []),
  ])

  @include('cashier.notes.partials.correction-history')
</div>
