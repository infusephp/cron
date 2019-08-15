<?php

namespace Infuse\Cron\Libs;

use Exception;
use Infuse\Cron\Events\CronJobBeginEvent;
use Infuse\Cron\Events\CronJobFinishedEvent;
use Infuse\Cron\Models\CronJob;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Lock\Factory;

class Runner
{
    use LoggerAwareTrait;

    /**
     * @var CronJob
     */
    private $jobModel;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var Lock
     */
    private $lock;

    /**
     * @var string
     */
    private $class;

    /**
     * @param CronJob         $job
     * @param string          $class       callable job class
     * @param EventDispatcher $dispatcher
     * @param Factory         $lockFactory
     * @param string          $namespace
     */
    public function __construct(CronJob $job, $class, EventDispatcher $dispatcher, Factory $lockFactory, $namespace = '')
    {
        $this->jobModel = $job;
        $this->class = $class;
        $this->dispatcher = $dispatcher;
        $this->lock = new Lock($this->jobModel->id, $lockFactory, $namespace);
    }

    /**
     * Gets the job model for this runner.
     *
     * @return CronJob
     */
    public function getJobModel()
    {
        return $this->jobModel;
    }

    /**
     * Gets the job class for this runner.
     *
     * @return string
     */
    public function getJobClass()
    {
        return $this->class;
    }

    /**
     * Runs a scheduled job.
     *
     * @param int          $expires    time the job has to finish
     * @param string|false $successUrl URL to be called upon a successful run
     * @param Run          $run
     *
     * @return Run result
     */
    public function go($expires = 0, $successUrl = false, Run $run = null)
    {
        if (!$run) {
            $run = new Run();
        }

        // only run the job if we can get the lock
        if (!$this->lock->acquire($expires)) {
            return $run->setResult(Run::RESULT_LOCKED);
        }

        // call the `cron_job.begin` event
        $event = new CronJobBeginEvent($this->jobModel->id);
        $this->dispatcher->dispatch($event, $event::NAME);
        if ($event->isPropagationStopped()) {
            $run->writeOutput('Rejected by cron_job.begin event listener')
                ->setResult(Run::RESULT_FAILED);
        }

        // set up the callable
        $job = $this->setUp($this->class, $run);

        // this is where the job actually gets called
        if ($job && !$event->isPropagationStopped()) {
            $this->invoke($job, $run);
        }

        // perform post-run tasks:
        // call the `cron_job.finished` event
        $event = new CronJobFinishedEvent($this->jobModel->id, $run->getResult());
        $this->dispatcher->dispatch($event, $event::NAME);

        // persist the result
        $this->saveRun($run);

        // ping success URL
        if ($run->succeeded()) {
            $this->pingSuccessUrl($successUrl, $run);
        }

        // release the lock
        $this->lock->release();

        return $run;
    }

    /**
     * Sets up an invokable class for a scheduled job run.
     *
     * @param string $class
     * @param Run    $run
     *
     * @return callable|false
     */
    private function setUp($class, Run $run)
    {
        if (!$class) {
            $run->writeOutput("Missing `class` parameter on {$this->jobModel->id} job")
                ->setResult(Run::RESULT_FAILED);

            return false;
        }

        if (!class_exists($class)) {
            $run->writeOutput("$class does not exist")
                ->setResult(Run::RESULT_FAILED);

            return false;
        }

        $job = new $class();

        // inject the DI container if needed
        if (method_exists($job, 'setApp')) {
            $job->setApp($this->jobModel->getApp());
        }

        return $job;
    }

    /**
     * Executes the actual job.
     *
     * @param Run $run
     *
     * @return Run
     */
    private function invoke(callable $job, Run $run)
    {
        try {
            ob_start();

            $ret = call_user_func($job, $run);
            $result = Run::RESULT_SUCCEEDED;
            if (false === $ret) {
                $result = Run::RESULT_FAILED;
            }

            return $run->writeOutput(ob_get_clean())
                       ->setResult($result);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("An uncaught exception occurred while running the {$this->jobModel->id()} scheduled job.", ['exception' => $e]);
            }

            return $run->writeOutput(ob_get_clean())
                       ->writeOutput($e->getMessage())
                       ->setResult(Run::RESULT_FAILED);
        }
    }

    /**
     * Saves the run attempt.
     *
     * @param Run $run
     */
    private function saveRun(Run $run)
    {
        $this->jobModel->last_ran = time();
        $this->jobModel->last_run_succeeded = $run->succeeded();
        $this->jobModel->last_run_output = $run->getOutput();
        $this->jobModel->save();
    }

    /**
     * @deprecated should be moved to an event listener
     *
     * Pings a URL about a successful run
     *
     * @param string $url
     * @param Run    $run
     */
    private function pingSuccessUrl($url, Run $run)
    {
        if (!$url) {
            return;
        }

        $url .= '?m='.urlencode($run->getOutput());
        @file_get_contents($url);
    }
}
