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

use App\Cron\Libs\Cron;
use App\Cron\Libs\CronDate;
use Infuse\Model;
use Infuse\Model\ACLModel;

define('CRON_JOB_SUCCESS', 1);
define('CRON_JOB_LOCKED', 2);
define('CRON_JOB_CONTROLLER_NON_EXISTENT', 3);
define('CRON_JOB_METHOD_NON_EXISTENT', 4);
define('CRON_JOB_FAILED', 5);

class CronJob extends ACLModel
{
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

    protected function hasPermission($permission, Model $requester)
    {
        return $requester->isAdmin();
    }

    public function __construct($id = false)
    {
        parent::__construct($id);
    }

    public function lockName()
    {
        return $this->app['config']->get('site.hostname').':'.
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

        $r = $this->app['redis'];
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

        $this->app['redis']->del($this->lockName());

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
            return CRON_JOB_LOCKED;
        }

        // attempt to execute the job
        $success = false;
        $output = '';

        $class = 'App\\'.$this->module.'\\Controller';

        if (class_exists($class)) {
            try {
                ob_start();

                $controller = new $class();

                if (method_exists($controller, 'injectApp')) {
                    $controller->injectApp($this->app);
                }

                $command = $this->command;
                if (!method_exists($controller, $command)) {
                    ob_end_clean();

                    return CRON_JOB_METHOD_NON_EXISTENT;
                } else {
                    $success = $controller->$command();
                }

                $output = ob_get_clean();
            } catch (\Exception $e) {
                $output = ob_get_clean();
                $output .= "\n".$e->getMessage();
            }
        } else {
            return CRON_JOB_CONTROLLER_NON_EXISTENT;
        }

        $this->set([
            'last_ran' => time(),
            'last_run_result' => $success,
            'last_run_output' => $output, ]);

        // ping the success URL
        if ($success && $successUrl && $this->app['config']->get('site.production-level')) {
            file_get_contents($successUrl.'?m='.urlencode($output));
        }

        $this->releaseLock();

        return ($success) ? CRON_JOB_SUCCESS : CRON_JOB_FAILED;
    }

    /**
     * Gets all jobs that are due to be ran and current lock values.
     *
     * @return array(model => CronJob, lock => lock value)
     */
    public static function overdueJobs()
    {
        $jobsFromConfig = (array) self::$injectedApp['config']->get('cron');

        $jobs = [];

        // round current time down to nearest minute
        $start = floor(time() / 60) * 60;

        foreach ($jobsFromConfig as $job) {
            $job = array_replace([
                'minute' => '*',
                'hour' => '*',
                'day' => '*',
                'week' => '*',
                'month' => '*',
                'successUrl' => '',
                'expires' => 0, ], $job);

            // check if overdue
            $nextRun = self::calcNextRun($job);

            if ($nextRun > $start) {
                continue;
            }

            // check if model has already been created for the job
            $model = new self([$job[ 'module'], $job['command']]);

            if (!$model->exists()) {
                $model = new self();
                $model->grantAllPermissions();
                $model->create([
                    'module' => $job['module'],
                    'command' => $job['command'], ]);
            }

            $job['model'] = $model;
            $jobs[] = $job;
        }

        return $jobs;
    }

    /**
     * Generates a timestamp from cron date parameters.
     *
     * @param array $params cron date parameters (minute, hour, day, month, week)
     *
     * @return int timestamp
     */
    public static function calcNextRun(array $params)
    {
        $cron_date = new CronDate(time());
        $cron_date->second = 0;

        if ($params[ 'minute' ] == '*') {
            $params[ 'minute' ] = null;
        }
        if ($params[ 'hour' ] == '*') {
            $params[ 'hour' ] = null;
        }
        if ($params[ 'day' ] == '*') {
            $params[ 'day' ] = null;
        }
        if ($params[ 'month' ] == '*') {
            $params[ 'month' ] = null;
        }
        if ($params[ 'week' ] == '*') {
            $params[ 'week' ] = null;
        }

        $Job = [
            'Minute' => $params[ 'minute' ],
            'Hour' => $params[ 'hour' ],
            'Day' => $params[ 'day' ],
            'Month' => $params[ 'month' ],
            'DOW' => $params[ 'week' ], ];

        $done = 0;
        while ($done < 100) {
            if (!is_null($Job['Minute']) && ($cron_date->minute != $Job['Minute'])) {
                if ($cron_date->minute > $Job['Minute']) {
                    $cron_date->modify('+1 hour');
                }
                $cron_date->minute = $Job['Minute'];
            }
            if (!is_null($Job['Hour']) && ($cron_date->hour != $Job['Hour'])) {
                if ($cron_date->hour > $Job['Hour']) {
                    $cron_date->modify('+1 day');
                }
                $cron_date->hour = $Job['Hour'];
                $cron_date->minute = 0;
            }
            if (!is_null($Job['DOW']) && ($cron_date->dow != $Job['DOW'])) {
                $cron_date->dow = $Job['DOW'];
                $cron_date->hour = 0;
                $cron_date->minute = 0;
            }
            if (!is_null($Job['Day']) && ($cron_date->day != $Job['Day'])) {
                if ($cron_date->day > $Job['Day']) {
                    $cron_date->modify('+1 month');
                }
                $cron_date->day = $Job['Day'];
                $cron_date->hour = 0;
                $cron_date->minute = 0;
            }
            if (!is_null($Job['Month']) && ($cron_date->month != $Job['Month'])) {
                if ($cron_date->month > $Job['Month']) {
                    $cron_date->modify('+1 year');
                }
                $cron_date->month = $Job['Month'];
                $cron_date->day = 1;
                $cron_date->hour = 0;
                $cron_date->minute = 0;
            }

            $done = (is_null($Job['Minute']) || $Job['Minute'] == $cron_date->minute) &&
                    (is_null($Job['Hour']) || $Job['Hour'] == $cron_date->hour) &&
                    (is_null($Job['Day']) || $Job['Day'] == $cron_date->day) &&
                    (is_null($Job['Month']) || $Job['Month'] == $cron_date->month) &&
                    (is_null($Job['DOW']) || $Job['DOW'] == $cron_date->dow) ? 100 : ($done + 1);
        }

        return $cron_date->timestamp;
    }
}
