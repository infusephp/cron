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
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param OutputInterface $output
     *
     * @return bool true if all tasks ran successfully
     */
    public function run(OutputInterface $output)
    {
        $output->writeln('-- Starting Cron');

        $success = true;

        foreach ($this->getScheduledJobs() as $jobInfo) {
            $job = $jobInfo['model'];
            $result = $this->runJob($job, $jobInfo, $output);

            $success = $result && $success;
        }

        return $success;
    }

    /**
     * Runs a scheduled job.
     *
     * @param CronJob         $job
     * @param array           $jobInfo
     * @param OutputInterface $output
     *
     * @return bool succeeded?
     */
    private function runJob(CronJob $job, array $jobInfo, OutputInterface $output)
    {
        $output->writeln("-- Starting {$job->module}.{$job->command}:");

        $result = $job->run($jobInfo['expires'], $jobInfo['successUrl']);

        if ($result == CronJob::SUCCESS) {
            $output->writeln($job->last_run_output);
            $output->writeln('-- Success!');
        } elseif ($result == CronJob::LOCKED) {
            $output->writeln("{$job->module}.{$job->command} locked!");
        } elseif ($result == CronJob::FAILED) {
            $output->writeln($job->last_run_output);
            $output->writeln('-- Failed!');
        }

        return $result == CronJob::SUCCESS;
    }
}
