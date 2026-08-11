<?php

namespace App\Services\Tenancy;

use App\Domain\Tenancy\Enums\EvaluationPeriodType;
use Illuminate\Support\Carbon;

/**
 * Builds period keys and Arabic labels for hierarchical evaluations.
 */
class EvaluationPeriodCatalog
{
    /**
     * @return list<array{key: string, label: string}>
     */
    public function options(EvaluationPeriodType $type, ?Carbon $around = null, int $yearsBack = 1, int $yearsForward = 0): array
    {
        $around ??= now();
        $startYear = $around->year - $yearsBack;
        $endYear = $around->year + $yearsForward;
        $options = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $options = array_merge($options, match ($type) {
                EvaluationPeriodType::Monthly => $this->monthlyOptions($year),
                EvaluationPeriodType::Quarterly => $this->quarterlyOptions($year),
                EvaluationPeriodType::SemiAnnually => $this->semiAnnualOptions($year),
                EvaluationPeriodType::Annually => [[
                    'key' => (string) $year,
                    'label' => 'سنة '.$year,
                ]],
            });
        }

        return $options;
    }

    public function currentKey(EvaluationPeriodType $type, ?Carbon $at = null): string
    {
        $at ??= now();

        return match ($type) {
            EvaluationPeriodType::Monthly => sprintf('%d-M%02d', $at->year, $at->month),
            EvaluationPeriodType::Quarterly => sprintf('%d-Q%d', $at->year, (int) ceil($at->month / 3)),
            EvaluationPeriodType::SemiAnnually => sprintf('%d-H%d', $at->year, $at->month <= 6 ? 1 : 2),
            EvaluationPeriodType::Annually => (string) $at->year,
        };
    }

    public function label(EvaluationPeriodType $type, string $key): string
    {
        foreach ($this->options($type, yearsBack: 5, yearsForward: 1) as $option) {
            if ($option['key'] === $key) {
                return $option['label'];
            }
        }

        return $key;
    }

    public function isValidKey(EvaluationPeriodType $type, string $key): bool
    {
        return (bool) preg_match(match ($type) {
            EvaluationPeriodType::Monthly => '/^\d{4}-M(0[1-9]|1[0-2])$/',
            EvaluationPeriodType::Quarterly => '/^\d{4}-Q[1-4]$/',
            EvaluationPeriodType::SemiAnnually => '/^\d{4}-H[12]$/',
            EvaluationPeriodType::Annually => '/^\d{4}$/',
        }, $key);
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function monthlyOptions(int $year): array
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        $options = [];

        foreach ($months as $month => $label) {
            $options[] = [
                'key' => sprintf('%d-M%02d', $year, $month),
                'label' => $label.' '.$year,
            ];
        }

        return $options;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function quarterlyOptions(int $year): array
    {
        return [
            ['key' => "{$year}-Q1", 'label' => "الربع الأول {$year}"],
            ['key' => "{$year}-Q2", 'label' => "الربع الثاني {$year}"],
            ['key' => "{$year}-Q3", 'label' => "الربع الثالث {$year}"],
            ['key' => "{$year}-Q4", 'label' => "الربع الرابع {$year}"],
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function semiAnnualOptions(int $year): array
    {
        return [
            ['key' => "{$year}-H1", 'label' => "النصف الأول {$year}"],
            ['key' => "{$year}-H2", 'label' => "النصف الثاني {$year}"],
        ];
    }
}
