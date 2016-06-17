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
use Infuse\Test;

class JobScheduleTest extends PHPUnit_Framework_TestCase
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

        Test::$app['db']->delete('CronJobs')
                        ->where('module', 'test')
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

        $this->assertInstanceOf('App\Cron\Models\CronJob', $jobs[0]['model']);
        $this->assertEquals('test', $jobs[0]['model']->module);
        $this->assertEquals('success_with_url', $jobs[0]['model']->command);

        $this->assertInstanceOf('App\Cron\Models\CronJob', $jobs[1]['model']);
        $this->assertEquals('test', $jobs[1]['model']->module);
        $this->assertEquals('success', $jobs[1]['model']->command);
    }

    public function testRun()
    {
        $output = Mockery::mock('Symfony\Component\Console\Output\OutputInterface');
        $output->shouldReceive('writeln')
               ->times(4);

        $schedule = new JobSchedule(self::$jobs);

        $this->assertTrue($schedule->run($output));

        // running the schedule should remove the
        // `success_with_url` job from the schedule
        $jobs = $schedule->getScheduledJobs();
        $this->assertCount(1, $jobs);
        $this->assertEquals('success', $jobs[0]['model']->command);
    }
}
