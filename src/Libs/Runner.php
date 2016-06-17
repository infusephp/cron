<?php

namespace App\Cron\Libs;

use App\Cron\Models\CronJob;
use Exception;

class Runner
{
    /**
     * @var CronJob
     */
    private $job;

    /**
     * @var Lock
     */
    private $lock;

    /**
     * @var CronJob
     */
    public function __construct(CronJob $job)
    {
        $this->job = $job;

        // build the job lock
        $lock = new Lock($this->job->module, $this->job->command);
        $lock->setApp($this->job->getApp());
        $this->lock = $lock;
    }

    /**
     * Gets the job associated with this runner.
     *
     * @return CronJob
     */
    public function getJob()
    {
        return $this->job;
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

        // once the lock is obtained we can attempt to run the job
        $this->invokeJob($run);

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
     * Invokes the actual job.
     *
     * @param Run $run
     *
     * @return Run
     */
    private function invokeJob(Run $run)
    {
        $class = 'App\\'.$this->job->module.'\Controller';

        if (!class_exists($class)) {
            return $run->writeOutput("$class does not exist")
                       ->setResult(Run::RESULT_FAILED);
        }

        try {
            ob_start();

            $controller = new $class();

            if (method_exists($controller, 'setApp')) {
                $controller->setApp($this->job->getApp());
            }

            $command = $this->job->command;
            if (!method_exists($controller, $command)) {
                ob_end_clean();

                return $run->setResult(Run::RESULT_FAILED)
                           ->writeOutput("{$this->job->module}->{$this->job->command}() does not exist");
            }

            // this is where the job actually gets called
            $ret = $controller->$command($run);
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
        $this->job->last_ran = time();
        $this->job->last_run_result = $run->succeeded();
        $this->job->last_run_output = $run->getOutput();
        $this->job->save();
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
