<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Task $task,
        public ?int $actorUserId = null,
    ) {}
}
