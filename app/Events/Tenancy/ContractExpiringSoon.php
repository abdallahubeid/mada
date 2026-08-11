<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Models\EmployeeContract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractExpiringSoon
{
    use Dispatchable, SerializesModels;

    public function __construct(public EmployeeContract $contract) {}
}
