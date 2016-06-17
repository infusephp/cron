<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace App\Cron\Libs;

use App\Cron\Models\CronJob;
use Infuse\Application;
use Infuse\Test;
use Mockery;

function file_get_contents($cmd)
{
    return RunnerTest::$functions->file_get_contents($cmd);
}

class RunnerTest extends \PHPUnit_Framework_TestCase
{
    public static $functions;

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

    public function setUp()
    {
        self::$functions = Mockery::mock();
    }

    public function testGetJob()
    {
        $job = new CronJob();
        $runner = new Runner($job);
        $this->assertEquals($job, $runner->getJob());
    }

    public function testGoLocked()
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
        $runner = new Runner($job);

        $run = $runner->go(100);
        $this->assertInstanceOf('App\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_LOCKED, $run->getResult());

        $this->assertFalse($job->exists());
    }

    public function testGoClassDoesNotExist()
    {
        $job = new CronJob();
        $job->module = 'non_existent';
        $job->command = 'non_existent';
        $runner = new Runner($job);

        $run = $runner->go();
        $this->assertInstanceOf('App\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_FAILED, $run->getResult());

        $this->assertTrue($job->exists());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_result);
        $this->assertEquals('App\non_existent\Controller does not exist', $job->last_run_output);
    }

    public function testGoCommandDoesNotExist()
    {
        $job = new CronJob();
        $job->module = 'test';
        $job->command = 'non_existent';
        $runner = new Runner($job);

        $run = $runner->go();
        $this->assertInstanceOf('App\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_FAILED, $run->getResult());

        $this->assertTrue($job->exists());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_result);
        $this->assertEquals('test->non_existent() does not exist', $job->last_run_output);
    }

    public function testGoException()
    {
        $job = new CronJob();
        $job->module = 'test';
        $job->command = 'exception';
        $runner = new Runner($job);

        $run = $runner->go();
        $this->assertInstanceOf('App\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_FAILED, $run->getResult());

        $this->assertTrue($job->exists());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_result);
        $this->assertEquals("\ntest", $job->last_run_output);
    }

    public function testGoFailed()
    {
        $job = new CronJob();
        $job->module = 'test';
        $job->command = 'fail';
        $runner = new Runner($job);

        $run = $runner->go();
        $this->assertInstanceOf('App\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_FAILED, $run->getResult());

        $this->assertTrue($job->exists());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_result);
    }

    public function testGoSuccess()
    {
        $job = new CronJob();
        $job->module = 'test';
        $job->command = 'success';
        $runner = new Runner($job);

        $run = $runner->go();
        $this->assertInstanceOf('App\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_SUCCEEDED, $run->getResult());

        $this->assertTrue($job->exists());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertTrue($job->last_run_result);
        $this->assertEquals("test run obj\ntest", $job->last_run_output);
    }

    public function testGoSuccessWithUrl()
    {
        $job = new CronJob();
        $job->module = 'test';
        $job->command = 'success_with_url';
        $runner = new Runner($job);

        self::$functions->shouldReceive('file_get_contents')
                        ->with('http://webhook.example.com/?m=yay')
                        ->once();

        $run = $runner->go(0, 'http://webhook.example.com/');
        $this->assertInstanceOf('App\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_SUCCEEDED, $run->getResult());

        $this->assertTrue($job->exists());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertTrue($job->last_run_result);
        $this->assertEquals('yay', $job->last_run_output);
    }
}
