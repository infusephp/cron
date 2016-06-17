<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace App\Cron\Models;

use App\Cron\Libs\Lock;
use App\Cron\Libs\Run;
use Exception;
use Pulsar\Model;

class CronJob extends Model
{
    protected static $ids = ['module', 'command'];

    protected static $properties = [
        'module' => [
            'required' => true,
        ],
        'command' => [
            'required' => true,
        ],
        'last_ran' => [
            'type' => Model::TYPE_DATE,
            'null' => true,
        ],
        'last_run_result' => [
            'type' => Model::TYPE_BOOLEAN,
        ],
        'last_run_output' => [
            'null' => true,
        ],
    ];

    /**
     * Runs this cron job.
     *
     * @param int    $expires    time the job has to finish
     * @param string $successUrl URL to be called upon a successful run
     * @param Run    $run
     *
     * @return Run result
     */
    public function run($expires = 0, $successUrl = false, Run $run = null)
    {
        if (!$run) {
            $run = new Run();
        }

        $lock = new Lock($this->module, $this->command);
        $lock->setApp($this->getApp());

        // only run the job if we can get the lock
        if (!$lock->getLock($expires)) {
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
        $lock->releaseLock();

        return $run;
    }

    /**
     * Invokes the job.
     *
     * @param Run $run
     *
     * @return Run
     */
    private function invokeJob(Run $run)
    {
        $class = 'App\\'.$this->module.'\Controller';

        if (!class_exists($class)) {
            return $run->writeOutput("$class does not exist")
                       ->setResult(Run::RESULT_FAILED);
        }

        try {
            ob_start();

            $controller = new $class();

            if (method_exists($controller, 'setApp')) {
                $controller->setApp($this->getApp());
            }

            $command = $this->command;
            if (!method_exists($controller, $command)) {
                ob_end_clean();

                return $run->setResult(Run::RESULT_FAILED)
                           ->writeOutput("{$this->module}->{$this->command}() does not exist");
            }

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
        $this->last_ran = time();
        $this->last_run_result = $run->succeeded();
        $this->last_run_output = $run->getOutput();
        $this->save();
    }

    /**
     * Pings the success URL about the successful run.
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
