@php
  $statusText = trim((string) ($label ?? '-'));
  $statusKey = strtolower(str_replace(['_', '-'], ' ', $statusText));

  $statusTone = $tone ?? match (true) {
    str_contains($statusKey, 'cancel'),
    str_contains($statusKey, 'batal'),
    str_contains($statusKey, 'refund'),
    str_contains($statusKey, 'kembali') => 'danger',
    str_contains($statusKey, 'close'),
    str_contains($statusKey, 'lunas'),
    str_contains($statusKey, 'paid'),
    str_contains($statusKey, 'selesai') => 'success',
    default => 'info',
  };
@endphp

<span class="note-detail-status-badge note-detail-status-badge--{{ $statusTone }} fs-6">
  {{ $statusText !== '' ? $statusText : '-' }}
</span>
