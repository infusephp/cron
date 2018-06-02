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
use Mockery\Adapter\Phpunit\MockeryTestCase;

class CronJobTest extends MockeryTestCase
{
    public static function setUpBeforeClass()
    {
        Test::$app['database']->getDefault()
            ->delete('CronJobs')
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
