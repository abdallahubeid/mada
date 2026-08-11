<?php

namespace App\Http\Requests\Tenant\Finance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A payroll run has no directly editable financial fields — every figure
 * derives from the Work Ledger and the line items. Editing is therefore scoped
 * to run metadata plus draft line-item amounts (BR-601).
 */
class UpdatePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.payroll.prepare') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('notes') === '') {
            $this->merge(['notes' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],

            // Amounts arrive in MAJOR units from the form and are converted to
            // minor units in the controller (ADR-20). Signs are derived from
            // each line's own kind, so a magnitude is all the form submits.
            'line_items' => ['nullable', 'array'],
            'line_items.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'line_items.*.numeric' => 'قيمة البند يجب أن تكون رقماً.',
            'line_items.*.min' => 'قيمة البند لا يمكن أن تكون سالبة — تحدد الإشارة من نوع البند.',
        ];
    }
}
