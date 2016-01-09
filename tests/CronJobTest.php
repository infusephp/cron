<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace App\Cron\Models;

use Mockery;
use Infuse\Application;
use Infuse\Test;

function file_get_contents($cmd)
{
    return CronJobTest::$functions->file_get_contents($cmd);
}

class CronJobTest extends \PHPUnit_Framework_TestCase
{
    public static $functions;
    public static $job;

    public static function setUpBeforeClass()
    {
        include_once 'Controller.php';

        Test::$app['db']->delete('CronJobs')->where('module', 'test')->execute();
    }

    public static function tearDownAfterClass()
    {
        self::$job->delete();
    }

    public function setUp()
    {
        self::$functions = Mockery::mock();
    }

    public function testCalcNextRun()
    {
        $input = [
            'minute' => '*',
            'hour' => '*',
            'day' => '*',
            'month' => '*',
            'week' => '*',
        ];

        // should be the next minute
        $expected = floor(time() / 60) * 60;
        $this->assertEquals($expected, CronJob::calcNextRun($input));

        $input = [
            'minute' => 0,
            'hour' => 0,
            'day' => 1,
            'month' => 0,
            'week' => 0,
        ];

        // should be the next Monday that is the first day of the month at 12:00
        // TODO this fails when ran on a Monday
        $expected = mktime(0, 0, 0, date('n'), 1, date('Y'));
        while (date('D', $expected) != 'Mon') {
            $expected = strtotime('+1 month', $expected);
        }

        $this->assertEquals($expected, CronJob::calcNextRun($input));
    }

    public function testCreate()
    {
        self::$job = new CronJob();
        $this->assertTrue(self::$job->create([
            'module' => 'test',
            'command' => 'test', ]));
    }

    /**
     * @depends testCreate
     */
    public function testOverdueJobs()
    {
        $overdue = CronJob::overdueJobs();

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
        $this->assertEquals('*', $job2['minute']);
        $this->assertEquals('*', $job2['hour']);
        $this->assertEquals('*', $job2['week']);
        $this->assertEquals('*', $job2['day']);
        $this->assertEquals('*', $job2['month']);
        $this->assertEquals(0, $job2['expires']);
        $this->assertEquals('', $job2['successUrl']);
        $this->assertTrue($job2['model']->exists());
    }

    public function testGetLock()
    {
        $job = new CronJob();
        $this->assertTrue($job->getLock());

        $app = new Application();
        $app['config']->set('app.hostname', 'example.com');
        $redis = Mockery::mock();
        $redis->shouldReceive('setnx')->withArgs(['example.com:cron.module.command', 100])->andReturn(true)->once();
        $redis->shouldReceive('del')->withArgs(['example.com:cron.module.command'])->andReturn(true)->once();
        $redis->shouldReceive('expire')->withArgs(['example.com:cron.module.command', 100])->andReturn(true)->once();
        $app['redis'] = $redis;
        CronJob::inject($app);

        $job = new CronJob();
        $job->module = 'module';
        $job->command = 'command';
        $this->assertTrue($job->getLock(100));
        $job->releaseLock();
    }

    public function testRunLocked()
    {
        $app = new Application();
        $app['config']->set('app.hostname', 'example.com');
        $redis = Mockery::mock();
        $redis->shouldReceive('setnx')->withArgs(['example.com:cron.module.command', 100])->andReturn(false)->once();
        $app['redis'] = $redis;
        CronJob::inject($app);

        $job = new CronJob();
        $job->module = 'module';
        $job->command = 'command';
        $this->assertEquals(CRON_JOB_LOCKED, $job->run(100));
    }

    public function testRunControllerNonExistent()
    {
        $job = new CronJob();
        $job->module = 'non_existent';
        $this->assertEquals(CRON_JOB_CONTROLLER_NON_EXISTENT, $job->run());
    }

    public function testCommandNonExistent()
    {
        $job = new CronJob();
        $job->module = 'test';
        $job->command = 'non_existent';
        $this->assertEquals(CRON_JOB_METHOD_NON_EXISTENT, $job->run());
    }

    /**
     * @depends testCreate
     */
    public function testRunExcpetion()
    {
        self::$job->module = 'test';
        self::$job->command = 'exception';
        $this->assertEquals(CRON_JOB_FAILED, self::$job->run());
        $this->assertEquals("\ntest", self::$job->last_run_output);
    }

    /**
     * @depends testCreate
     */
    public function testRunSuccess()
    {
        self::$job->module = 'test';
        self::$job->command = 'success';
        $this->assertEquals(CRON_JOB_SUCCESS, self::$job->run(0, 'http://webhook.example.com/'));
        $this->assertEquals('test', self::$job->last_run_output);
    }

    /**
     * @depends testCreate
     */
    public function testRunSuccessWithUrl()
    {
        self::$job->module = 'test';
        self::$job->command = 'success';
        self::$functions->shouldReceive('file_get_contents')->with('http://webhook.example.com/?m=test')->once();
        Test::$app['config']->set('app.production-level', true);
        $this->assertEquals(CRON_JOB_SUCCESS, self::$job->run(0, 'http://webhook.example.com/'));
        $this->assertEquals('test', self::$job->last_run_output);
    }
}
