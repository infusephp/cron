<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Cron\Libs\JobSchedule;
use Infuse\Test;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class JobScheduleTest extends MockeryTestCase
{
    public static $jobs = [
        [
          'module' => 'test',
          'command' => 'success_with_url',
          'minute' => 0,
          'hour' => 0,
        ],
        [
          'module' => 'test',
          'command' => 'success',
        ],
    ];

    public static function setUpBeforeClass()
    {
        include_once 'Controller.php';

        Test::$app['database']->getDefault()
            ->delete('CronJobs')
            ->where('id', 'test%', 'like')
            ->execute();
    }

    public function testGetAllJobs()
    {
        $schedule = new JobSchedule(self::$jobs);
        $this->assertEquals(self::$jobs, $schedule->getAllJobs());
    }

    public function testGetScheduledJobs()
    {
        $schedule = new JobSchedule(self::$jobs);
        $jobs = $schedule->getScheduledJobs();

        $this->assertCount(2, $jobs);

        $this->assertInstanceOf('Infuse\Cron\Models\CronJob', $jobs[0]['model']);
        $this->assertEquals('test', $jobs[0]['model']->module);
        $this->assertEquals('success_with_url', $jobs[0]['model']->command);

        $this->assertInstanceOf('Infuse\Cron\Models\CronJob', $jobs[1]['model']);
        $this->assertEquals('test', $jobs[1]['model']->module);
        $this->assertEquals('success', $jobs[1]['model']->command);
    }

    public function testRunScheduled()
    {
        $output = Mockery::mock('Symfony\Component\Console\Output\OutputInterface');
        $output->shouldReceive('writeln')
               ->times(7);

        $schedule = new JobSchedule(self::$jobs);

        $this->assertTrue($schedule->runScheduled($output));

        // running the schedule should remove the
        // `success_with_url` job from the schedule
        $jobs = $schedule->getScheduledJobs();
        $this->assertCount(1, $jobs);
        $this->assertEquals('success', $jobs[0]['model']->command);
    }
}
