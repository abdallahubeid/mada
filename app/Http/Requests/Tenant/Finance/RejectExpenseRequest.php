<?php

namespace App\Http\Requests\Tenant\Finance;

use Illuminate\Foundation\Http\FormRequest;

class RejectExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.expenses.approve') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // BR-905: a rejection without a reason gives the claimant nothing
            // to correct, and they can resubmit this same record.
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'يجب ذكر سبب رفض المصروف.',
        ];
    }
}
