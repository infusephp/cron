<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace app\cron\libs;

use app\cron\models\CronJob;

class Cron
{
    /**
     * Checks the cron schedule and runs tasks.
     *
     * @param string $output append output to this variable
     *
     * @return bool true if all tasks ran successfully
     */
    public static function scheduleCheck(&$output = '')
    {
        $output .= "-- Starting Cron\n";

        $success = true;

        foreach (CronJob::overdueJobs() as $jobInfo) {
            $job = $jobInfo['model'];

            $output .= "-- Starting {$job->module}.{$job->command}:\n";

            $result = $job->run($jobInfo['expires'], $jobInfo['successUrl']);
            $jobOutput = $job->last_run_output;

            if ($result == CRON_JOB_LOCKED) {
                $output .= "{$job->module}.{$job->command} locked!\n";
            } elseif ($result == CRON_JOB_CONTROLLER_NON_EXISTENT) {
                $output .= "{$job->module} does not exist\n";
            } elseif ($result == CRON_JOB_METHOD_NON_EXISTENT) {
                $output .= "{$job->module}\-\>{$job->command}() does not exist\n";
            } elseif ($result == CRON_JOB_FAILED) {
                if ($jobOutput) {
                    $output .= "$jobOutput\n";
                }
                $output .= "-- Failed!\n";
            } elseif ($result == CRON_JOB_SUCCESS) {
                if ($jobOutput) {
                    $output .= "$jobOutput\n";
                }
                $output .= "-- Success!\n";
            }

            $success = $result == CRON_JOB_SUCCESS && $success;
        }

        return $success;
    }
}
