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
    public static $jobs = [
        [
          'module' => 'test',
          'command' => 'test',
          'expires' => 60,
          'successUrl' => 'http://webhook.example.com',
          'minute' => 0,
          'hour' => 0,
          'day' => '*',
          'month' => '*',
          'week' => '*',
        ],
        [
          'module' => 'test',
          'command' => 'test2',
        ],
    ];

    public function testGetAllJobs()
    {
        $schedule = new JobSchedule(self::$jobs);
        $this->assertEquals(self::$jobs, $schedule->getAllJobs());
    }

    public function testGetScheduledJobs()
    {
        $schedule = new JobSchedule(self::$jobs);
        $overdue = $schedule->getScheduledJobs();

        $this->assertCount(2, $overdue);
        $job1 = $overdue[0];
        $job2 = $overdue[1];

        $this->assertEquals('test', $job1['module']);
        $this->assertEquals('test', $job1['command']);
        $this->assertEquals(0, $job1['minute']);
        $this->assertEquals(0, $job1['hour']);
        $this->assertEquals(60, $job1['expires']);
        $this->assertEquals('http://webhook.example.com', $job1['successUrl']);

        $this->assertEquals('test', $job1['module']);
        $this->assertEquals('test2', $job2['command']);
        $this->assertEquals(0, $job2['expires']);
        $this->assertEquals('', $job2['successUrl']);
        $this->assertTrue($job2['model']->exists());
    }

    public function testRun()
    {
        $this->markTestIncomplete();
    }
}
