<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace App\Cron\Libs;

use App\Cron\Models\CronJob;

class JobSchedule
{
    /**
     * @var array
     */
    private $jobs;

    /**
     * @var array
     */
    public function __construct(array $jobs)
    {
        $this->jobs = $jobs;
    }

    /**
     * Gets all of the available scheduled jobs.
     *
     * @return array
     */
    public function getAllJobs()
    {
        return $this->jobs;
    }

    /**
     * Gets all of the jobs scheduled to run now.
     *
     * @return array
     */
    public function getScheduledJobs()
    {
        return CronJob::overdueJobs();
    }

    /**
     * Runs any scheduled tasks.
     *
     * @param string $output append output to this variable
     *
     * @return bool true if all tasks ran successfully
     */
    public function run(&$output = '')
    {
        $output .= "-- Starting Cron\n";

        $success = true;

        foreach ($this->getScheduledJobs() as $jobInfo) {
            $job = $jobInfo['model'];
            list($result, $jobOutput) = $this->runJob($job, $jobInfo);

            $success = $result == CRON_JOB_SUCCESS && $success;
            $output .= $jobOutput;
        }

        return $success;
    }

    /**
     * Runs a scheduled job.
     *
     * @param CronJob $job
     * @param array   $jobInfo
     *
     * @return array array(result, output)
     */
    private function runJob(CronJob $job, array $jobInfo)
    {
        $output = "-- Starting {$job->module}.{$job->command}:\n";

        $result = $job->run($jobInfo['expires'], $jobInfo['successUrl']);
        $jobOutput = $job->last_run_output;

        if ($result == CRON_JOB_LOCKED) {
            $output .= "{$job->module}.{$job->command} locked!\n";
        } elseif ($result == CRON_JOB_CONTROLLER_NON_EXISTENT) {
            $output .= "{$job->module} does not exist\n";
        } elseif ($result == CRON_JOB_METHOD_NON_EXISTENT) {
            $output .= "{$job->module}\-\>{$job->command}() does not exist\n";
        } elseif ($result == CRON_JOB_FAILED) {
            if ($jobOutput) {
                $output .= "$jobOutput\n";
            }
            $output .= "-- Failed!\n";
        } elseif ($result == CRON_JOB_SUCCESS) {
            if ($jobOutput) {
                $output .= "$jobOutput\n";
            }
            $output .= "-- Success!\n";
        }

        return [$result, $output];
    }
}
