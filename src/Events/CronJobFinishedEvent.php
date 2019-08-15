<?php

namespace Infuse\Cron\Events;

use Symfony\Contracts\EventDispatcher\Event;

class CronJobFinishedEvent extends Event
{
    const NAME = 'cron_job.finished';

    /**
     * @var string
     */
    protected $jobId;

    /**
     * @var string
     */
    protected $result;

    /**
     * @param string $jobId
     * @param string $result
     */
    public function __construct($jobId, $result)
    {
        $this->jobId = $jobId;
        $this->result = $result;
    }

    /**
     * @return string
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }
}
