<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Models\JobApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobApplicationSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public JobApplication $application) {}
}
