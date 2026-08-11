<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\Employee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public ?EmployeeStatus $previousStatus,
        public ?EmployeeStatus $newStatus,
        public bool $deleted = false,
        public ?int $actorUserId = null,
    ) {}
}
