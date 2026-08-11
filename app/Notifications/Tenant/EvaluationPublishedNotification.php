<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\EmployeeEvaluation;
use App\Services\Tenancy\EvaluationPeriodCatalog;
use Illuminate\Support\Facades\Route;

class EvaluationPublishedNotification extends TenantNotification
{
    public function __construct(public EmployeeEvaluation $evaluation)
    {
        $this->evaluation->loadMissing('evaluator');
    }

    protected function title(): string
    {
        return 'صدر تقييم أدائك';
    }

    protected function message(): string
    {
        $period = app(EvaluationPeriodCatalog::class)->label(
            $this->evaluation->period_type,
            $this->evaluation->period_key,
        );

        $rating = $this->evaluation->rating !== null
            ? number_format((float) $this->evaluation->rating, 2).' / 5'
            : null;

        return $rating === null
            ? "تم نشر تقييم فترة {$period}."
            : "تم نشر تقييم فترة {$period} بتقدير {$rating}.";
    }

    protected function url(): ?string
    {
        return Route::has('tenant.hr.my-evaluations')
            ? route('tenant.hr.my-evaluations')
            : null;
    }

    protected function icon(): string
    {
        return 'evaluation';
    }

    protected function severity(): string
    {
        return 'medium';
    }

    protected function type(): string
    {
        return 'evaluation.published';
    }
}
