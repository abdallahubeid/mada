<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.applications.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ApplicationStatus::values())],
            'cover_letter' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
