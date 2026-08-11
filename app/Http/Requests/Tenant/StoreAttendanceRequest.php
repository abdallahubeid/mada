<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.attendance.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['check_in', 'check_out', 'notes', 'status'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'date' => ['nullable', 'date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', Rule::in(AttendanceStatus::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
