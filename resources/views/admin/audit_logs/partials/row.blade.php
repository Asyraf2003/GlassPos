<tr>
    <td>{{ $entry['id'] }}</td>
    <td class="text-nowrap">
        {{ \App\Support\ViewDateFormatter::display($entry['created_at'] ?? null, true) }}
    </td>
    <td>
        <span class="badge bg-light-secondary text-secondary">
            {{ $entry['source'] }}
        </span>
    </td>
    <td>
        <span class="badge bg-light-primary text-primary">
            {{ $entry['event'] }}
        </span>
    </td>
    <td>
        <div>{{ $entry['actor_id'] ?? '-' }}</div>
        @if (!empty($entry['actor_role']))
            <div class="small text-muted">{{ $entry['actor_role'] }}</div>
        @endif
    </td>
    <td>
        <div>{{ $entry['entity_type'] ?? '-' }}</div>
        @if (!empty($entry['entity_id']))
            <div class="small text-muted">{{ $entry['entity_id'] }}</div>
        @endif
        @if (!empty($entry['bounded_context']))
            <div class="small text-muted">{{ $entry['bounded_context'] }}</div>
        @endif
    </td>
    <td>{{ $entry['reason'] }}</td>
    <td><a href="{{ route('admin.audit-logs.show', ['source' => $entry['source'], 'auditId' => $entry['id']]) }}" class="btn btn-sm btn-light-primary">Detail</a></td>
</tr>
