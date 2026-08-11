<?php

namespace App\Http\Requests\Tenant\Finance;

use Illuminate\Foundation\Http\FormRequest;

class RejectPayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.payroll.approve') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // BR-905: a rejection without a reason gives the maker nothing to act on.
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'يجب ذكر سبب رفض مسيرة الرواتب.',
        ];
    }
}
