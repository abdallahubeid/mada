<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Models\EmployeeEvaluation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An evaluation became visible to the employee it belongs to — i.e. it moved
 * to Submitted or Approved, the two statuses `myEvaluations()` exposes.
 */
class EvaluationPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public EmployeeEvaluation $evaluation,
        public ?int $actorUserId = null,
    ) {}
}
