<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantPortalSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tenant.settings.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $booleans = [
            'is_portal_enabled',
            'is_hero_active',
            'is_about_active',
            'is_services_active',
            'is_culture_active',
            'is_stats_active',
            'is_careers_active',
            'is_faq_active',
            'is_cta_active',
            'is_contact_active',
        ];

        $merged = [];
        foreach ($booleans as $field) {
            $merged[$field] = $this->boolean($field);
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_portal_enabled' => ['required', 'boolean'],

            'hero_badge_text' => ['nullable', 'string', 'max:120'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:2000'],
            'hero_primary_cta_text' => ['nullable', 'string', 'max:120'],
            'hero_primary_cta_url' => ['nullable', 'string', 'max:500'],
            'hero_secondary_cta_text' => ['nullable', 'string', 'max:120'],
            'hero_secondary_cta_url' => ['nullable', 'string', 'max:500'],
            'is_hero_active' => ['required', 'boolean'],

            'about_title' => ['nullable', 'string', 'max:255'],
            'about_subtitle' => ['nullable', 'string', 'max:255'],
            'vision_text' => ['nullable', 'string', 'max:5000'],
            'mission_text' => ['nullable', 'string', 'max:5000'],
            'values_json' => ['nullable', 'array'],
            'values_json.*.title' => ['nullable', 'string', 'max:120'],
            'values_json.*.desc' => ['nullable', 'string', 'max:1000'],
            'is_about_active' => ['required', 'boolean'],

            'services_title' => ['nullable', 'string', 'max:255'],
            'services_subtitle' => ['nullable', 'string', 'max:255'],
            'services_json' => ['nullable', 'array'],
            'services_json.*.title' => ['nullable', 'string', 'max:120'],
            'services_json.*.description' => ['nullable', 'string', 'max:1000'],
            'services_json.*.icon' => ['nullable', 'string', 'max:40'],
            'is_services_active' => ['required', 'boolean'],

            'culture_title' => ['nullable', 'string', 'max:255'],
            'culture_subtitle' => ['nullable', 'string', 'max:255'],
            'culture_perks_json' => ['nullable', 'array'],
            'culture_perks_json.*.title' => ['nullable', 'string', 'max:120'],
            'culture_perks_json.*.description' => ['nullable', 'string', 'max:1000'],
            'is_culture_active' => ['required', 'boolean'],

            'stats_title' => ['nullable', 'string', 'max:255'],
            'stats_json' => ['nullable', 'array'],
            'stats_json.*.label' => ['nullable', 'string', 'max:120'],
            'stats_json.*.value' => ['nullable'],
            'stats_json.*.suffix' => ['nullable', 'string', 'max:20'],
            'is_stats_active' => ['required', 'boolean'],

            'careers_badge_text' => ['nullable', 'string', 'max:120'],
            'careers_title' => ['nullable', 'string', 'max:255'],
            'careers_subtitle' => ['nullable', 'string', 'max:1000'],
            'is_careers_active' => ['required', 'boolean'],

            'faq_title' => ['nullable', 'string', 'max:255'],
            'faq_subtitle' => ['nullable', 'string', 'max:255'],
            'faqs_json' => ['nullable', 'array'],
            'faqs_json.*.question' => ['nullable', 'string', 'max:500'],
            'faqs_json.*.answer' => ['nullable', 'string', 'max:5000'],
            'is_faq_active' => ['required', 'boolean'],

            'cta_title' => ['nullable', 'string', 'max:255'],
            'cta_subtitle' => ['nullable', 'string', 'max:2000'],
            'cta_button_text' => ['nullable', 'string', 'max:120'],
            'is_cta_active' => ['required', 'boolean'],

            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:60'],
            'contact_address' => ['nullable', 'string', 'max:500'],
            'office_hours' => ['nullable', 'string', 'max:255'],
            'map_embed_url' => ['nullable', 'string', 'max:2000'],
            'is_contact_active' => ['required', 'boolean'],
        ];
    }
}
