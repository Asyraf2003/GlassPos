<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\IdentityAccess;

use Illuminate\Foundation\Http\FormRequest;

final class DisableAdminTransactionCapabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'target_actor_id' => ['required', 'string'],
        ];
    }
}
