<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Models\Employee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Employee $employee) {}
}
