<?php

namespace App\Http\Requests\Tenant;

use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates an interview booking plus the invitation the HR Manager composed.
 *
 * Note what is NOT here: a `to` field. The candidate's address comes from the
 * application record inside the action, never from the request — an editable
 * recipient would make this screen a way to send mail from the tenant's domain
 * to anyone.
 */
class ScheduleInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.recruitment.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        // The CC field is a single free-text input; operators separate addresses
        // with commas, semicolons or newlines depending on where they pasted
        // them from. Normalise before validating so the error messages point at
        // the address, not at the separator.
        $this->merge(['cc' => $this->normalizedCc()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            /*
             * The interviewer must be an ACTIVE user of THIS tenant. Without the
             * tenant clause a guessed id from another tenant would both book the
             * interview and deliver them a notification carrying the candidate's
             * name — a cross-tenant leak through a foreign key.
             */
            'interviewer_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                ),
            ],

            'scheduled_at' => ['required', 'date', 'after:now'],
            'location_or_link' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'email_subject' => ['required', 'string', 'max:255'],
            'email_body' => ['required', 'string', 'max:20000'],

            'cc' => ['nullable', 'array', 'max:10'],
            'cc.*' => ['email'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'interviewer_id.required' => 'يجب اختيار المحاور.',
            'interviewer_id.exists' => 'المحاور المحدد غير متاح في هذه المؤسسة.',
            'scheduled_at.required' => 'يجب تحديد موعد المقابلة.',
            'scheduled_at.after' => 'موعد المقابلة يجب أن يكون في المستقبل.',
            'email_subject.required' => 'موضوع البريد مطلوب.',
            'email_body.required' => 'نص البريد مطلوب.',
            'cc.*.email' => 'أحد عناوين النسخة (CC) غير صالح.',
            'cc.max' => 'لا يمكن إضافة أكثر من 10 عناوين في النسخة (CC).',
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizedCc(): array
    {
        $raw = $this->input('cc');

        if (is_array($raw)) {
            $parts = $raw;
        } elseif (is_string($raw)) {
            $parts = preg_split('/[,;\r\n]+/', $raw) ?: [];
        } else {
            return [];
        }

        return collect($parts)
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
