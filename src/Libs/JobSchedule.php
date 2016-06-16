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
     * @var array list of available jobs
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
     * Gets all of the jobs scheduled to run, now.
     *
     * @return array(model => CronJob)
     */
    public function getScheduledJobs()
    {
        // round current time down to nearest minute
        $start = floor(time() / 60) * 60;

        $jobs = [];
        foreach ($this->jobs as $job) {
            // check if scheduled to run
            $params = new DateParameters($job);
            $date = new CronDate($params);
            if ($date->getNextRun() > $start) {
                continue;
            }

            $job = array_replace([
                'successUrl' => '',
                'expires' => 0, ], $job);

            // check if model has already been created for the job
            $model = new CronJob([$job['module'], $job['command']]);

            if (!$model->exists()) {
                $model = new CronJob();
                $model->module = $job['module'];
                $model->command = $job['command'];
            }

            $job['model'] = $model;
            $jobs[] = $job;
        }

        return $jobs;
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

            $success = $result == CronJob::SUCCESS && $success;
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

        if ($result == CronJob::LOCKED) {
            $output .= "{$job->module}.{$job->command} locked!\n";
        } elseif ($result == CronJob::CONTROLLER_NON_EXISTENT) {
            $output .= "{$job->module} does not exist\n";
        } elseif ($result == CronJob::METHOD_NON_EXISTENT) {
            $output .= "{$job->module}\-\>{$job->command}() does not exist\n";
        } elseif ($result == CronJob::FAILED) {
            if ($jobOutput) {
                $output .= "$jobOutput\n";
            }
            $output .= "-- Failed!\n";
        } elseif ($result == CronJob::SUCCESS) {
            if ($jobOutput) {
                $output .= "$jobOutput\n";
            }
            $output .= "-- Success!\n";
        }

        return [$result, $output];
    }
}
