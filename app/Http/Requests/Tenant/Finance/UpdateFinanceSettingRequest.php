<?php

namespace App\Http\Requests\Tenant\Finance;

use App\Domain\Finance\Models\FinanceSetting;
use App\Domain\Finance\Support\EosbPolicy;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the EOSB rules and converts the screen's percentages into the
 * basis points the domain stores.
 *
 * The form talks in percent because that is how a statute is written ("half a
 * month's wage per year" = 50%). The database talks in basis points because a
 * third of an entitlement is 33.33%, and a rate that feeds a payment must not
 * be held as a float (ADR-20 applies to the multiplier as much as the amount).
 */
class UpdateFinanceSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.settings.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        // An unchecked checkbox posts nothing at all.
        $this->merge(['eosb_enabled' => $this->boolean('eosb_enabled')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'eosb_enabled' => ['required', 'boolean'],

            /*
             * 600 months = 50 years of service. A ceiling exists only to keep a
             * typo from producing an entitlement nobody notices is wrong.
             */
            'eosb_tier_boundary_months' => ['required', 'integer', 'min:0', 'max:600'],

            /*
             * Percent of a month's wage accrued per year of service. Capped at
             * 300% — three months per year is already far beyond any statute we
             * are aware of, and an uncapped field here multiplies straight into
             * the payout.
             */
            'eosb_lower_tier_percent' => ['required', 'numeric', 'min:0', 'max:300', 'decimal:0,2'],
            'eosb_upper_tier_percent' => ['required', 'numeric', 'min:0', 'max:300', 'decimal:0,2'],

            'eosb_resignation_taper' => ['nullable', 'array', 'max:12'],
            'eosb_resignation_taper.*.months' => ['required', 'integer', 'min:0', 'max:600'],
            'eosb_resignation_taper.*.percent' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],

            'nominal_month_days' => ['required', 'integer', 'min:1', 'max:31'],
            'nominal_day_hours' => ['required', 'integer', 'min:1', 'max:24'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'eosb_resignation_taper.*.months.required' => 'يجب تحديد عدد الأشهر لكل شريحة في جدول الاستقالة.',
            'eosb_resignation_taper.*.percent.required' => 'يجب تحديد النسبة المستحقة لكل شريحة في جدول الاستقالة.',
            'eosb_lower_tier_percent.decimal' => 'النسبة تقبل رقمين عشريين على الأكثر.',
            'eosb_upper_tier_percent.decimal' => 'النسبة تقبل رقمين عشريين على الأكثر.',
            'eosb_resignation_taper.*.percent.decimal' => 'النسبة تقبل رقمين عشريين على الأكثر.',
        ];
    }

    /**
     * The validated input in the shape {@see FinanceSetting}
     * stores, with every percentage already converted to basis points.
     *
     * @return array<string, mixed>
     */
    public function settingAttributes(): array
    {
        return [
            'eosb_enabled' => (bool) $this->validated('eosb_enabled'),
            'eosb_tier_boundary_months' => (int) $this->validated('eosb_tier_boundary_months'),
            'eosb_lower_tier_bps' => self::percentToBasisPoints($this->validated('eosb_lower_tier_percent')),
            'eosb_upper_tier_bps' => self::percentToBasisPoints($this->validated('eosb_upper_tier_percent')),
            'eosb_resignation_taper' => $this->taperBands(),
            'nominal_month_days' => (int) $this->validated('nominal_month_days'),
            'nominal_day_hours' => (int) $this->validated('nominal_day_hours'),
        ];
    }

    /**
     * Ascending bands, deduplicated on `months` — a repeated threshold would
     * make the payable rate depend on row order in the form.
     *
     * @return list<array{months: int, bps: int}>
     */
    private function taperBands(): array
    {
        $rows = $this->validated('eosb_resignation_taper') ?? [];

        $bands = [];

        foreach ($rows as $row) {
            $bands[(int) $row['months']] = [
                'months' => (int) $row['months'],
                'bps' => self::percentToBasisPoints($row['percent']),
            ];
        }

        if ($bands === []) {
            return EosbPolicy::defaultResignationTaper();
        }

        ksort($bands);

        return array_values($bands);
    }

    /**
     * "33.33" → 3333 basis points, by string surgery rather than
     * `(int) round($percent * 100)`.
     *
     * The float route is accurate enough at these magnitudes, but the module's
     * rule is that no float participates in a figure that reaches a payment,
     * and this multiplier does. Values are already `decimal:0,2` by the time
     * they arrive, so at most two fractional digits exist to read.
     */
    private static function percentToBasisPoints(mixed $percent): int
    {
        $value = trim((string) ($percent ?? '0'));

        if ($value === '') {
            return 0;
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        return max(0, (int) $whole) * 100 + (int) $fraction;
    }
}
