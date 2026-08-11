<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CompleteSetupWizardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->tenant !== null
            && ! $user->tenant->isActive()
            && $user->can('tenant.settings.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'logo' => ['nullable', 'image', 'max:2048'],
            'currency' => ['required', 'string', 'size:3', Rule::in(['SAR', 'AED', 'EGP', 'USD', 'EUR'])],
            'timezone' => ['required', 'timezone:all'],
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
