<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Infuse\Cron\Tests;

use Infuse\Cron\Models\CronJob;
use Infuse\Test;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class CronJobTest extends MockeryTestCase
{
    public static $job;

    public static function setUpBeforeClass()
    {
        Test::$app['database']->getDefault()
            ->delete('CronJobs')
            ->where('id', 'test%', 'like')
            ->execute();
    }

    public function testCreate()
    {
        self::$job = new CronJob();
        self::$job->id = 'test.test';
        $this->assertTrue(self::$job->save());
    }

    /**
     * @depends testCreate
     */
    public function testEdit()
    {
        self::$job->last_ran = time();
        $this->assertTrue(self::$job->save());
    }
}
