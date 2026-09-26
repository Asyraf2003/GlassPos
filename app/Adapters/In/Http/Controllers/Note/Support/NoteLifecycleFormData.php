<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Note\Support;

use App\Application\Note\Services\NoteLifecycleDetailBuilder;
use App\Ports\Out\UuidPort;
use Illuminate\Http\Request;

final class NoteLifecycleFormData
{
    public function __construct(private readonly NoteLifecycleDetailBuilder $details, private readonly UuidPort $uuid) {}

    public function build(Request $request, string $noteId, string $area): array
    {
        $view = $this->details->build($noteId, (string) $request->user()?->getAuthIdentifier());
        if (! in_array($view['mode'], ['cancel', 'restore'], true)) {
            return $view;
        }
        $currentBase = $view['base_revision_id'];
        $mode = $view['mode'];
        $view['action'] = route($area.'.notes.'.$mode, ['noteId' => $noteId]);
        $retry = $request->old('lifecycle_form') === $noteId.':'.$mode;
        $view['form_id'] = $noteId.':'.$mode;
        $view['reason'] = $retry ? (string) $request->old('reason', '') : '';
        $view['idempotency_key'] = $retry ? (string) $request->old('idempotency_key', '') : $this->uuid->generate();
        // Keep the observed identity with the retry key; never rebase a stale form implicitly.
        foreach (['base_revision_id', 'source_revision_id', 'cancellation_event_id'] as $field) {
            $view[$field] = $retry ? (string) $request->old($field, $view[$field]) : $view[$field];
        }
        $view['stale'] = $view['base_revision_id'] !== $currentBase;

        return $view;
    }
}
