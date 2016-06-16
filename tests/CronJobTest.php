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

    public function testCreate()
    {
        self::$job = new CronJob();
        $this->assertTrue(self::$job->create([
            'module' => 'test',
            'command' => 'test', ]));
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
        $this->assertEquals(CronJob::LOCKED, $job->run(100));
    }

    public function testRunControllerNonExistent()
    {
        $job = new CronJob();
        $job->module = 'non_existent';
        $this->assertEquals(CronJob::CONTROLLER_NON_EXISTENT, $job->run());
    }

    public function testCommandNonExistent()
    {
        $job = new CronJob();
        $job->module = 'test';
        $job->command = 'non_existent';
        $this->assertEquals(CronJob::METHOD_NON_EXISTENT, $job->run());
    }

    /**
     * @depends testCreate
     */
    public function testRunExcpetion()
    {
        self::$job->module = 'test';
        self::$job->command = 'exception';
        $this->assertEquals(CronJob::FAILED, self::$job->run());
        $this->assertEquals("\ntest", self::$job->last_run_output);
    }

    /**
     * @depends testCreate
     */
    public function testRunSuccess()
    {
        self::$job->module = 'test';
        self::$job->command = 'success';
        $this->assertEquals(CronJob::SUCCESS, self::$job->run(0, 'http://webhook.example.com/'));
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
        $this->assertEquals(CronJob::SUCCESS, self::$job->run(0, 'http://webhook.example.com/'));
        $this->assertEquals('test', self::$job->last_run_output);
    }
}
