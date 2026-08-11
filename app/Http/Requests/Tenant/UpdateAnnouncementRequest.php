<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\Enums\AnnouncementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tenant.announcements.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', 'max:10000'],
            'type' => ['required', Rule::in(AnnouncementType::values())],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'is_pinned' => ['sometimes', 'boolean'],
        ];
    }
}
