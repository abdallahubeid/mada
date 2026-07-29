<?php

namespace App\Http\Requests\Admin;

use App\Domain\Platform\PlatformPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:125',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique(config('permission.table_names.roles'), 'name')
                    ->where(fn ($query) => $query
                        ->where('guard_name', PlatformPermissionCatalog::GUARD)
                        ->whereNull('tenant_id')),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(PlatformPermissionCatalog::all())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'معرّف الدور يجب أن يبدأ بحرف لاتيني صغير ويحتوي أحرفًا وأرقامًا وشرطة سفلية فقط.',
            'name.unique' => 'هذا المعرّف مستخدم لدور آخر.',
        ];
    }
}
