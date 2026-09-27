<span
  class="note-detail-status-badge note-detail-status-badge--{{ \App\Support\NoteStatusBadgeFormatter::tone($label ?? null, $tone ?? null) }}"
  style="font-size: .8rem !important; min-height: 1.4rem; padding: .08rem .42rem; line-height: 1; color: #fff !important;"
>
  {{ \App\Support\NoteStatusBadgeFormatter::label($label ?? null) }}
</span>
