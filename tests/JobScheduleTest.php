<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use App\Cron\Libs\JobSchedule;

class JobScheduleTest extends PHPUnit_Framework_TestCase
{
    public function testGetAllJobs()
    {
        $jobs = [
            [
                'module' => 'test',
                'command' => 'test',
            ],
        ];
        $schedule = new JobSchedule($jobs);
        $this->assertEquals($jobs, $schedule->getAllJobs());
    }

    public function testGetScheduledJobs()
    {
        $this->markTestIncomplete();
    }

    public function testRun()
    {
        $this->markTestIncomplete();
    }
}
