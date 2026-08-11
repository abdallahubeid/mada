<?php

namespace App\Http\Requests\Tenant\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.payroll.prepare') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // YYYY-MM. The builder re-validates this and owns the real guard —
            // the regex here only keeps a malformed value out of the domain.
            'period' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period.regex' => 'صيغة الفترة يجب أن تكون سنة-شهر (مثال: 2026-08).',
        ];
    }
}
