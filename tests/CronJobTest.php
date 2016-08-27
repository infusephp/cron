<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Cron\Models\CronJob;
use Infuse\Test;

class CronJobTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Test::$app['db']->delete('CronJobs')
                        ->where('id', 'test%', 'like')
                        ->execute();
    }

    public function testCreate()
    {
        $job = new CronJob();
        $job->id = 'test.test';
        $job->module = 'test';
        $job->command = 'test';
        $this->assertTrue($job->save());
    }
}
