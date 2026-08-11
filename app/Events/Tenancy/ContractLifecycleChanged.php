<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Models\EmployeeContract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractLifecycleChanged
{
    use Dispatchable, SerializesModels;

    /**
     * @param  'created'|'updated'|'terminated'  $action
     */
    public function __construct(
        public EmployeeContract $contract,
        public string $action,
    ) {}
}
