<?php

namespace Infuse\Cron\Events;

use Symfony\Contracts\EventDispatcher\Event;

class CronJobBeginEvent extends Event
{
    const NAME = 'cron_job.begin';

    /**
     * @var string
     */
    protected $jobId;

    /**
     * @param string $jobId
     */
    public function __construct($jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * @return string
     */
    public function getJobId()
    {
        return $this->jobId;
    }
}
