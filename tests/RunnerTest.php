<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Infuse\Cron\Libs;

use Infuse\Cron\Models\CronJob;
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
        include_once 'TestJob.php';
        include_once 'Controller.php';

        Test::$app['database']->getDefault()
            ->delete('CronJobs')
            ->where('id', 'test%', 'like')
            ->execute();

        Test::$app['database']->getDefault()
            ->delete('CronJobs')
            ->where('id', 'non_existent%', 'like')
            ->execute();
    }

    public function setUp()
    {
        self::$functions = Mockery::mock();
    }

    public function testGetJobModel()
    {
        $job = new CronJob();
        $runner = new Runner($job, '');
        $this->assertEquals($job, $runner->getJobModel());
    }

    public function testGetClass()
    {
        $job = new CronJob();
        $runner = new Runner($job, 'test');
        $this->assertEquals('test', $runner->getJobClass());

        $job->module = 'test';
        $runner = new Runner($job, '');
        $this->assertEquals('App\test\Controller', $runner->getJobClass());
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
        $job->id = 'test.locked';
        $job->module = 'test';
        $job->command = 'locked';
        $runner = new Runner($job, '');

        $run = $runner->go(100);
        $this->assertInstanceOf('Infuse\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_LOCKED, $run->getResult());

        $this->assertFalse($job->persisted());
    }

    public function testGoClassDoesNotExist()
    {
        $job = new CronJob();
        $job->id = 'non_existent.non_existent';
        $job->module = 'non_existent';
        $job->command = 'non_existent';
        $runner = new Runner($job, '');

        $run = $runner->go();
        $this->assertInstanceOf('Infuse\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_FAILED, $run->getResult());

        $this->assertTrue($job->persisted());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_succeeded);
        $this->assertEquals('App\non_existent\Controller does not exist', $job->last_run_output);
    }

    public function testGoCommandDoesNotExist()
    {
        $job = new CronJob();
        $job->id = 'test.non_existent';
        $job->module = 'test';
        $job->command = 'non_existent';
        $runner = new Runner($job, '');

        $run = $runner->go();
        $this->assertInstanceOf('Infuse\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_FAILED, $run->getResult());

        $this->assertTrue($job->persisted());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_succeeded);
        $this->assertEquals('App\test\Controller->non_existent() does not exist', $job->last_run_output);
    }

    public function testGoException()
    {
        $job = new CronJob();
        $job->id = 'test.exception';
        $job->module = 'test';
        $job->command = 'exception';
        $runner = new Runner($job, '');

        $run = $runner->go();
        $this->assertInstanceOf('Infuse\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_FAILED, $run->getResult());

        $this->assertTrue($job->persisted());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_succeeded);
        $this->assertEquals('test', $job->last_run_output);
    }

    public function testGoFailed()
    {
        $job = new CronJob();
        $job->id = 'test.fail';
        $job->module = 'test';
        $job->command = 'fail';
        $runner = new Runner($job, '');

        $run = $runner->go();
        $this->assertInstanceOf('Infuse\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_FAILED, $run->getResult());

        $this->assertTrue($job->persisted());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_succeeded);
    }

    public function testGoSuccess()
    {
        $job = new CronJob();
        $job->id = 'test.success';
        $job->module = 'test';
        $job->command = 'success';
        $runner = new Runner($job, '');

        $run = $runner->go();
        $this->assertInstanceOf('Infuse\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_SUCCEEDED, $run->getResult());

        $this->assertTrue($job->persisted());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertTrue($job->last_run_succeeded);
        $this->assertEquals("test run obj\ntest", $job->last_run_output);
    }

    public function testGoSuccessClass()
    {
        $job = new CronJob();
        $job->id = 'test.invoke';
        $runner = new Runner($job, 'App\Test\TestJob');

        $run = $runner->go();
        $this->assertInstanceOf('Infuse\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_SUCCEEDED, $run->getResult());

        $this->assertTrue($job->persisted());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertTrue($job->last_run_succeeded);
        $this->assertEquals('works', $job->last_run_output);
    }

    public function testGoSuccessWithUrl()
    {
        $job = new CronJob();
        $job->id = 'test.success_with_url';
        $job->module = 'test';
        $job->command = 'success_with_url';
        $runner = new Runner($job, '');

        self::$functions->shouldReceive('file_get_contents')
                        ->with('http://webhook.example.com/?m=yay')
                        ->once();

        $run = $runner->go(0, 'http://webhook.example.com/');
        $this->assertInstanceOf('Infuse\Cron\Libs\Run', $run);
        $this->assertEquals(Run::RESULT_SUCCEEDED, $run->getResult());

        $this->assertTrue($job->persisted());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertTrue($job->last_run_succeeded);
        $this->assertEquals('yay', $job->last_run_output);
    }
}
