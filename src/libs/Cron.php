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

use App;
use app\cron\models\CronJob;

class Cron
{
    /**
	 * Checks the cron schedule and runs tasks
	 *
	 * @param App $app DI container
	 * @param boolean $echoOutput echoes output
	 *
	 * @return boolean true if all tasks ran successfully
	 */
    public static function scheduleCheck(App $app, $echoOutput = false)
    {
        if( $echoOutput )
            echo "-- Starting Cron on " . $app[ 'config' ]->get( 'site.title' ) . "\n";

        $success = true;

        foreach ( CronJob::overdueJobs() as $job ) {
            $taskSuccess = self::runJob( $job[ 'model' ], $job[ 'expires' ], $app, $echoOutput );

            $success = $taskSuccess && $success;
        }

        return $success;
    }

    /**
	 * Runs a specific cron job
	 *
	 * @param CronJob job
	 * @param int $expires time the job has to finish
	 * @param App $app DI container
	 * @param boolean $echoOutput
	 *
	 * @return boolean result
	 */
    public static function runJob(CronJob $job, $expires, App $app, $echoOutput = false)
    {
        // only run the job if we can get the lock
        if ( !$job->getLock( $expires ) ) {
            if( $echoOutput )
                echo "{$job->module}.{$job->command} locked!\n";

            return true;
        }

        // attempt to execute the job
        $success = false;
        $output = '';

        try {
            $controller = '\\app\\' . $job->module . '\\Controller';

            if ( class_exists( $controller ) ) {
                if( $echoOutput )
                    echo "Starting {$job->module}.{$job->command}:\n";

                ob_start();

                $controllerObj = new $controller;

                if (method_exists($controllerObj, 'injectApp'))
                    $controllerObj->injectApp($app);

                if( !method_exists( $controllerObj, 'cron' ) )
                    echo "$controller\-\>cron($command) does not exist\n";
                else
                    $success = $controllerObj->cron( $job->command );

                $output = ob_get_clean();
            } else {
                $output = "{$job->module} does not exist";
            }
        } catch ( \Exception $e ) {
            $output .= "\n" . $e->getMessage();
        }

        $job->set( [
            'last_ran' => time(),
            'last_run_result' => $success,
            'last_run_output' => $output ] );

        $job->releaseLock();

        if( $echoOutput )
            echo $output . (( $success ) ? "\tFinished Successfully\n" : "\tFailed\n");

        return $success;
    }
}
