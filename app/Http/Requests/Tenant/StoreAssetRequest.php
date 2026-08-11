<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Enums\AssetCategory;
use App\Domain\Tenancy\Enums\AssetStatus;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.assets.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            'name' => ['required', 'string', 'max:180'],
            'asset_code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('tenant_assets', 'asset_code')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'category' => ['required', Rule::in(AssetCategory::values())],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'status' => ['nullable', Rule::in([
                AssetStatus::Available->value,
                AssetStatus::UnderMaintenance->value,
                AssetStatus::Retired->value,
            ])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
