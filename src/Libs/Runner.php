<?php

namespace App\Cron\Libs;

use App\Cron\Models\CronJob;
use Exception;

class Runner
{
    /**
     * @var CronJob
     */
    private $jobModel;

    /**
     * @var Lock
     */
    private $lock;

    /**
     * @var string
     */
    private $class;

    /**
     * DEPRECATED this is kept for BC.
     *
     * @var string|null
     */
    private $module;

    /**
     * DEPRECATED this is kept for BC.
     *
     * @var string|null
     */
    private $command;

    /**
     * @var CronJob
     * @var string  $class callable job class
     */
    public function __construct(CronJob $job, $class)
    {
        $this->jobModel = $job;
        $this->class = $class;

        // build the job lock
        $lock = new Lock($this->jobModel->id);
        $lock->setApp($this->jobModel->getApp());
        $this->lock = $lock;

        // DEPRECATED this is kept for BC
        if (!$class && $job->module) {
            $this->withModuleDeprecated($job->module, $job->command);
        }
    }

    /**
     * Sets the callable class from a module and command argument.
     *
     * @var string|null deprecated module argument
     * @var string|null $command deprecated command argument
     *
     * @return self
     */
    public function withModuleDeprecated($module, $command)
    {
        $this->class = 'App\\'.$module.'\Controller';
        $this->module = $module;
        $this->command = $command;

        return $this;
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
     * @param int    $expires    time the job has to finish
     * @param string $successUrl URL to be called upon a successful run
     * @param Run    $run
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

        // set up the callable
        $job = $this->setUp($this->class, $run);

        // DEPRECATED this is kept for BC
        if ($job && $this->command) {
            $job = $this->setUpControllerDeprecated($job, $this->command, $run);
        }

        // this is where the job actually gets called
        if ($job) {
            $this->invoke($job, $run);
        }

        // perform post-run tasks:
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
     * Sets up the callable given a controller.
     * DEPRECATED this is maintained for BC.
     *
     * @param callable $controller
     * @param string   $command
     * @param Run      $run
     *
     * @return array|false
     */
    private function setUpControllerDeprecated($controller, $command, Run $run)
    {
        $command = $command;
        if (!method_exists($controller, $command)) {
            $run->setResult(Run::RESULT_FAILED)
                 ->writeOutput("{$this->class}->{$command}() does not exist");

            return false;
        }

        return [$controller, $command];
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
            if ($ret === false) {
                $result = Run::RESULT_FAILED;
            }

            return $run->writeOutput(ob_get_clean())
                       ->setResult($result);
        } catch (Exception $e) {
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
     * Pings a URL about a successful run.
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
