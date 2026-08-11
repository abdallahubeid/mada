<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Enums\TaskPriority;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.tasks.access') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        $managerId = Employee::query()->where('user_id', $this->user()?->id)->value('id') ?? 0;

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(
                    fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('manager_id', $managerId)
                        ->whereNull('deleted_at')
                ),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['nullable', Rule::in(TaskPriority::values())],
        ];
    }
}
