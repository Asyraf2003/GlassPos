<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

use Illuminate\Foundation\Http\FormRequest;

final class CancelNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'base_revision_id' => ['required', 'string', 'max:100'],
            'idempotency_key' => ['required', 'string', 'max:120'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
