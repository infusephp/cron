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
     * @return array array(model => CronJob)
     */
    public function getScheduledJobs()
    {
        $jobs = [];
        foreach ($this->jobs as $job) {
            $model = new CronJob([$job['module'], $job['command']]);

            // create a new model if this is the job's first run
            if (!$model->exists()) {
                $model = new CronJob();
                $model->module = $job['module'];
                $model->command = $job['command'];
            }

            // check if scheduled to run
            $params = new DateParameters($job);
            $date = new CronDate($params, $model->last_ran);

            if ($date->getNextRun() > time()) {
                continue;
            }

            $job = array_replace([
                'successUrl' => '',
                'expires' => 0,
            ], $job);

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
    public function runScheduled(OutputInterface $output)
    {
        $success = true;

        foreach ($this->getScheduledJobs() as $jobInfo) {
            $job = $jobInfo['model'];
            $run = $this->runJob($job, $jobInfo, $output);

            $success = $run->succeeded() && $success;
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
     * @return Run $run
     */
    private function runJob(CronJob $job, array $jobInfo, OutputInterface $output)
    {
        $output->writeln("-- Starting {$job->module}.{$job->command}:");

        $runner = new Runner($job);
        $run = new Run();
        $run->setConsoleOutput($output);
        $runner->go($jobInfo['expires'], $jobInfo['successUrl'], $run);

        if ($run->succeeded()) {
            $output->writeln('-- Success!');
        } elseif ($run->getResult() == Run::RESULT_LOCKED) {
            $output->writeln("{$job->module}.{$job->command} is locked!");
        } elseif ($run->failed()) {
            $output->writeln('-- Failed!');
        }

        return $run;
    }
}
