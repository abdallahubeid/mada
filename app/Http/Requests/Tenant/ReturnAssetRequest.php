<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Enums\AssetCondition;
use App\Domain\Tenancy\Enums\AssetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnAssetRequest extends FormRequest
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
        return [
            'returned_at' => ['nullable', 'date'],
            'condition_on_return' => ['required', Rule::in(AssetCondition::values())],
            'status' => ['required', Rule::in([
                AssetStatus::Available->value,
                AssetStatus::UnderMaintenance->value,
                AssetStatus::Retired->value,
            ])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
