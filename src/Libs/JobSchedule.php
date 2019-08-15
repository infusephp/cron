<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Infuse\Cron\Libs;

use Infuse\Cron\Events\ScheduleRunBeginEvent;
use Infuse\Cron\Events\ScheduleRunFinishedEvent;
use Infuse\Cron\Models\CronJob;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Lock\Factory;

class JobSchedule
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    private $jobs;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var Factory
     */
    private $lockFactory;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @param array list of available jobs
     * @param Factory $lockFactory
     * @param string  $namespace
     */
    public function __construct(array $jobs, Factory $lockFactory, $namespace = '')
    {
        $this->jobs = $jobs;
        $this->lockFactory = $lockFactory;
        $this->namespace = $namespace;
        $this->dispatcher = new EventDispatcher();
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
     * Gets the event dispatcher.
     *
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Registers a listener for an event.
     *
     * @param string   $eventName
     * @param callable $listener
     * @param int      $priority
     *
     * @return $this
     */
    public function listen($eventName, callable $listener, $priority = 0)
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);

        return $this;
    }

    /**
     * Registers an event subscriber.
     *
     * @param EventSubscriberInterface $subscriber
     *
     * @return $this
     */
    public function subscribe(EventSubscriberInterface $subscriber)
    {
        $this->dispatcher->addSubscriber($subscriber);

        return $this;
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
            $model = CronJob::find($job['id']);

            // create a new model if this is the job's first run
            if (!$model) {
                $model = new CronJob();
                $model->id = $job['id'];
                $model->save();
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

        $event = new ScheduleRunBeginEvent();
        $this->dispatcher->dispatch($event, $event::NAME);

        foreach ($this->getScheduledJobs() as $jobInfo) {
            $job = $jobInfo['model'];
            $run = $this->runJob($job, $jobInfo, $output);

            $success = $run->succeeded() && $success;
        }

        $event = new ScheduleRunFinishedEvent();
        $this->dispatcher->dispatch($event, $event::NAME);

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
        $output->writeln("-- Starting {$job->id}...");

        // set up the runner
        $class = array_value($jobInfo, 'class');
        $runner = new Runner($job, $class, $this->dispatcher, $this->lockFactory, $this->namespace);
        if ($this->logger) {
            $runner->setLogger($this->logger);
        }

        // set up an object to track this run
        $run = new Run();
        $run->setConsoleOutput($output);

        // and go!
        $runner->go($jobInfo['expires'], $jobInfo['successUrl'], $run);

        if ($run->succeeded()) {
            $output->writeln('-- Success!');
        } elseif (Run::RESULT_LOCKED == $run->getResult()) {
            $output->writeln("{$job->id} is locked!");
        } elseif ($run->failed()) {
            $output->writeln('-- Failed!');
        }

        return $run;
    }
}
