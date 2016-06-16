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
use Exception;
use Pulsar\Model;

class CronJob extends Model
{
    const SUCCESS = 1;
    const LOCKED = 2;
    const CONTROLLER_NON_EXISTENT = 3;
    const METHOD_NON_EXISTENT = 4;
    const FAILED = 5;

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
            'null' => true,
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
     *
     * @return int result
     */
    public function run($expires = 0, $successUrl = false)
    {
        $lock = new Lock($this->module, $this->command);
        $lock->setApp($this->getApp());

        // only run the job if we can get the lock
        if (!$lock->getLock($expires)) {
            return self::LOCKED;
        }

        // once the lock is obtained we can attempt to run the job
        list($result, $output) = $this->invokeJob();
        $success = $result == self::SUCCESS;

        // perform post-run tasks:
        // persist the result
        $this->saveRun($success, $output);

        // ping success URL
        if ($success) {
            $this->notifySuccessUrl($successUrl, $output);
        }

        // release the lock
        $lock->releaseLock();

        return $result;
    }

    /**
     * Invokes the job.
     *
     * @return array array(result, output)
     */
    private function invokeJob()
    {
        $class = 'App\\'.$this->module.'\Controller';

        if (!class_exists($class)) {
            return [self::CONTROLLER_NON_EXISTENT, ''];
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

                return [self::METHOD_NON_EXISTENT, ''];
            }

            $success = $controller->$command();

            $output = ob_get_clean();
            $result = $success ? self::SUCCESS : self::FAILED;

            return [$result, $output];
        } catch (Exception $e) {
            $output = ob_get_clean();
            $output .= "\n".$e->getMessage();

            return [self::FAILED, $output];
        }
    }

    /**
     * Saves the run attempt.
     *
     * @param bool   $success
     * @param string $output
     */
    private function saveRun($success, $output)
    {
        $this->last_ran = time();
        $this->last_run_result = $success;
        $this->last_run_output = $output;
        $this->save();
    }

    /**
     * Pings the success URL about the successful run.
     *
     * @param string $url
     * @param string $output
     */
    private function notifySuccessUrl($url, $output)
    {
        if (!$url) {
            return;
        }

        @file_get_contents($url.'?m='.urlencode($output));
    }
}
