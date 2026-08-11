<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Enums\EvaluationPeriodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tenant.settings.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'size:3', Rule::in(['SAR', 'AED', 'EGP', 'USD', 'EUR'])],
            'timezone' => ['required', 'timezone:all'],
            'evaluation_periodicity' => ['required', Rule::in(EvaluationPeriodType::values())],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['integer', 'between:0,6'],
            'holidays' => ['nullable', 'array'],
            'holidays.*.date' => ['nullable', 'required_with:holidays.*.name', 'date_format:Y-m-d'],
            'holidays.*.name' => ['nullable', 'required_with:holidays.*.date', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'working_days.required' => 'اختر يوم عمل واحد على الأقل.',
            'working_days.min' => 'اختر يوم عمل واحد على الأقل.',
        ];
    }
}
