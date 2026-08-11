<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\TrashableResourceCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrashBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(TrashableResourceCatalog::keys())],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'string', 'regex:/^[a-z0-9\-]+:\d+$/'],
        ];
    }

    /**
     * @return list<array{type: string, id: int}>
     */
    public function normalizedItems(): array
    {
        $keys = TrashableResourceCatalog::keys();
        $normalized = [];

        foreach ($this->validated('items') as $token) {
            [$type, $id] = explode(':', $token, 2);

            if (! in_array($type, $keys, true)) {
                continue;
            }

            $normalized[] = [
                'type' => $type,
                'id' => (int) $id,
            ];
        }

        return $normalized;
    }
}
