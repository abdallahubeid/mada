<?php

namespace App\Listeners\Platform;

use App\Services\Admin\PlatformNotificationPublisher;
use Illuminate\Queue\Events\JobFailed;

/**
 * Persist + broadcast a platform alert when a queued job fails.
 */
class RecordFailedJobNotification
{
    public function __construct(private PlatformNotificationPublisher $publisher) {}

    public function handle(JobFailed $event): void
    {
        $jobName = $event->job->resolveName();
        $message = $event->exception->getMessage();

        $this->publisher->jobFailed($jobName, $message);
    }
}
