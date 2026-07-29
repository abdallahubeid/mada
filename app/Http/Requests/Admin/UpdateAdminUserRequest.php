<?php

namespace App\Http\Requests\Admin;

use App\Domain\Platform\PlatformPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admins.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $admin */
        $admin = $this->route('admin');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => [
                'required',
                'string',
                Rule::exists(config('permission.table_names.roles'), 'name')
                    ->where(fn ($query) => $query
                        ->where('guard_name', PlatformPermissionCatalog::GUARD)
                        ->whereNull('tenant_id')),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(PlatformPermissionCatalog::all())],
        ];
    }
}
