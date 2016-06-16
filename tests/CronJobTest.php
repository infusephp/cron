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

        Test::$app['db']->delete('CronJobs')
                        ->where('module', 'test')
                        ->execute();

        Test::$app['db']->delete('CronJobs')
                        ->where('module', 'non_existent')
                        ->execute();
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
        self::$job->module = 'test';
        self::$job->command = 'test';
        $this->assertTrue(self::$job->save());
    }

    public function testRunLocked()
    {
        $app = new Application();
        $app['config']->set('app.hostname', 'example.com');
        $redis = Mockery::mock();
        $redis->shouldReceive('setnx')
              ->withArgs(['example.com:cron.test.locked', 100])
              ->andReturn(false)
              ->once();
        $app['redis'] = $redis;
        CronJob::inject($app);

        $job = new CronJob();
        $job->module = 'test';
        $job->command = 'locked';

        $this->assertEquals(CronJob::LOCKED, $job->run(100));

        $this->assertFalse($job->exists());
    }

    public function testRunClassDoesNotExist()
    {
        $job = new CronJob();
        $job->module = 'non_existent';
        $job->command = 'non_existent';

        $this->assertEquals(CronJob::FAILED, $job->run());

        $this->assertTrue($job->exists());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_result);
        $this->assertEquals('App\non_existent\Controller does not exist', $job->last_run_output);
    }

    public function testRunCommandDoesNotExist()
    {
        $job = new CronJob();
        $job->module = 'test';
        $job->command = 'non_existent';

        $this->assertEquals(CronJob::FAILED, $job->run());

        $this->assertTrue($job->exists());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_result);
        $this->assertEquals('test->non_existent() does not exist', $job->last_run_output);
    }

    public function testRunException()
    {
        $job = new CronJob();
        $job->module = 'test';
        $job->command = 'exception';

        $this->assertEquals(CronJob::FAILED, $job->run());

        $this->assertTrue($job->exists());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_result);
        $this->assertEquals("\ntest", $job->last_run_output);
    }

    public function testRunSuccess()
    {
        $job = new CronJob();
        $job->module = 'test';
        $job->command = 'success';

        $this->assertEquals(CronJob::SUCCESS, $job->run());

        $this->assertTrue($job->exists());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertTrue($job->last_run_result);
        $this->assertEquals('test', $job->last_run_output);
    }

    public function testRunSuccessWithUrl()
    {
        $job = new CronJob();
        $job->module = 'test';
        $job->command = 'success_with_url';
        self::$functions->shouldReceive('file_get_contents')
                        ->with('http://webhook.example.com/?m=yay')
                        ->once();

        $this->assertEquals(CronJob::SUCCESS, $job->run(0, 'http://webhook.example.com/'));

        $this->assertTrue($job->exists());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertTrue($job->last_run_result);
        $this->assertEquals('yay', $job->last_run_output);
    }
}
