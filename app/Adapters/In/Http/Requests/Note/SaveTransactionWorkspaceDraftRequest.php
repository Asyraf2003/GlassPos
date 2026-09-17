<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

use Illuminate\Foundation\Http\FormRequest;

final class SaveTransactionWorkspaceDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'workspace_mode' => $this->trimOrNull('workspace_mode'),
            'note_id' => $this->trimOrNull('note_id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'workspace_mode' => ['required', 'in:create,edit'],
            'base_revision_id' => ['nullable', 'string', 'max:255'],
            'note_id' => ['nullable', 'string'],
            'note' => ['nullable', 'array'],
            'items' => ['nullable', 'array'],
            'inline_payment' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function draftPayload(): array
    {
        return [
            'base_revision_id' => $this->input('base_revision_id'),
            'note' => is_array($this->input('note')) ? $this->input('note') : [],
            'items' => is_array($this->input('items')) ? array_values($this->input('items')) : [],
            'inline_payment' => is_array($this->input('inline_payment')) ? $this->input('inline_payment') : [],
        ];
    }

    public function workspaceKey(): string
    {
        $mode = (string) $this->input('workspace_mode');

        if ($mode === 'edit') {
            $noteId = (string) ($this->input('note_id') ?? '');

            return 'edit:' . trim($noteId);
        }

        return 'create';
    }

    private function trimOrNull(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
