<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkScheduleRequest extends FormRequest
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
            'work_start_time' => ['required', 'date_format:H:i'],
            'work_end_time' => ['required', 'date_format:H:i', 'after:work_start_time'],
            'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'weekend_days' => ['nullable', 'array'],
            'weekend_days.*' => ['integer', 'between:0,6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'work_end_time.after' => 'وقت نهاية العمل يجب أن يكون بعد وقت البداية.',
        ];
    }
}
