<?php

/**
 * @package infuse\framework
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace app\cron\libs;

use app\cron\models\CronJob;

class Cron
{
    /**
	 * Checks the cron schedule and runs tasks
	 *
	 * @param boolean $echoOutput echoes output
	 *
	 * @return boolean true if all tasks ran successfully
	 */
    public static function scheduleCheck($echoOutput = false)
    {
        if ($echoOutput) {
            echo "-- Starting Cron\n";
        }

        $success = true;

        foreach (CronJob::overdueJobs() as $jobInfo) {
            $job = $jobInfo['model'];

            if ($echoOutput) {
                echo "Starting {$job->module}.{$job->command}:\n";
            }

            $result = $job->run($jobInfo['expires'], $jobInfo['successUrl']);
            $output = $job->last_run_output;

            if ($echoOutput) {
                if ($result == CRON_JOB_LOCKED) {
                    echo "\t{$job->module}.{$job->command} locked!\n";
                } else if ($result == CRON_JOB_CONTROLLER_NON_EXISTENT) {
                    echo "\t{$job->module} does not exist\n";
                } else if ($result == CRON_JOB_METHOD_NON_EXISTENT) {
                    echo "\t{$job->module}\-\>{$job->command}() does not exist\n";
                } else if ($result == CRON_JOB_FAILED) {
                    echo "$output\n";
                    echo "\tFailed\n";
                } else if ($result == CRON_JOB_SUCCESS) {
                    echo "$output\n";
                    echo "\tFinished Successfully\n";
                }
            }

            $success = $result == CRON_JOB_SUCCESS && $success;
        }

        return $success;
    }
}
