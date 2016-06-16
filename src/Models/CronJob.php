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

use Pulsar\Model;

class CronJob extends Model
{
    const SUCCESS = 1;
    const LOCKED = 2;
    const CONTROLLER_NON_EXISTENT = 3;
    const METHOD_NON_EXISTENT = 4;
    const FAILED = 5;

    public static $scaffoldApi = true;

    protected static $ids = ['module', 'command'];

    protected static $properties = [
        'module' => [
            'required' => true,
       ],
        'command' => [
            'required' => true,
       ],
        'last_ran' => [
            'type' => Model::TYPE_NUMBER,
            'null' => true,
            'admin_type' => 'datepicker',
       ],
        'last_run_result' => [
            'type' => Model::TYPE_BOOLEAN,
            'null' => true,
            'admin_type' => 'checkbox',
       ],
        'last_run_output' => [
            'null' => true,
            'admin_type' => 'textarea',
            'admin_hidden_property' => true,
            'admin_html' => '<pre>{last_run_output}</pre>',
            'admin_truncate' => false,
       ],
   ];

    private $hasLock;

    public function lockName()
    {
        return $this->getApp()['config']->get('app.hostname').':'.
            'cron.'.$this->module.'.'.$this->command;
    }

    /**
     * Attempts to get the global lock for this job.
     *
     * @param int $expires time in which the lock expires
     *
     * @return bool
     */
    public function getLock($expires = 0)
    {
        // do not lock if expiry time is 0
        if ($expires <= 0) {
            return true;
        }

        $r = $this->getApp()['redis'];
        $lock = $this->lockName();

        if ($r->setnx($lock, $expires)) {
            $r->expire($lock, $expires);

            $this->hasLock = true;

            return true;
        }

        return false;
    }

    public function releaseLock()
    {
        if (!$this->hasLock) {
            return;
        }

        $this->getApp()['redis']->del($this->lockName());

        $this->hasLock = false;
    }

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
        // only run the job if we can get the lock
        if (!$this->getLock($expires)) {
            return self::LOCKED;
        }

        $app = $this->getApp();

        // attempt to execute the job
        $success = false;
        $output = '';

        $class = 'App\\'.$this->module.'\\Controller';

        if (class_exists($class)) {
            try {
                ob_start();

                $controller = new $class();

                if (method_exists($controller, 'setApp')) {
                    $controller->setApp($app);
                }

                $command = $this->command;
                if (!method_exists($controller, $command)) {
                    ob_end_clean();

                    return self::METHOD_NON_EXISTENT;
                } else {
                    $success = $controller->$command();
                }

                $output = ob_get_clean();
            } catch (\Exception $e) {
                $output = ob_get_clean();
                $output .= "\n".$e->getMessage();
            }
        } else {
            return self::CONTROLLER_NON_EXISTENT;
        }

        $this->set([
            'last_ran' => time(),
            'last_run_result' => $success,
            'last_run_output' => $output, ]);

        // ping the success URL
        if ($success && $successUrl && $app['config']->get('app.production-level')) {
            @file_get_contents($successUrl.'?m='.urlencode($output));
        }

        $this->releaseLock();

        return ($success) ? self::SUCCESS : self::FAILED;
    }
}
