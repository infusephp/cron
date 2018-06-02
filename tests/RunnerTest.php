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

use Infuse\Cron\Libs\FileGetContentsMock;
use Infuse\Cron\Libs\Run;
use Infuse\Cron\Libs\Runner;
use Infuse\Cron\Models\CronJob;
use Infuse\Test;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

class RunnerTest extends MockeryTestCase
{
    public static $lockFactory;

    public static function setUpBeforeClass()
    {
        include_once 'TestJob.php';
        include_once 'Controller.php';
        include_once 'file_get_contents_mock.php';

        Test::$app['database']->getDefault()
            ->delete('CronJobs')
            ->where('id', 'test%', 'like')
            ->execute();

        Test::$app['database']->getDefault()
            ->delete('CronJobs')
            ->where('id', 'non_existent%', 'like')
            ->execute();

        $store = new FlockStore(sys_get_temp_dir());
        self::$lockFactory = new Factory($store);
    }

    public function setUp()
    {
        FileGetContentsMock::$functions = Mockery::mock();
    }

    public function testGetJobModel()
    {
        $job = new CronJob();
        $runner = new Runner($job, '', self::$lockFactory);
        $this->assertEquals($job, $runner->getJobModel());
    }

    public function testGetClass()
    {
        $job = new CronJob();
        $runner = new Runner($job, 'test', self::$lockFactory);
        $this->assertEquals('test', $runner->getJobClass());

        $job->module = 'test';
        $runner = new Runner($job, '', self::$lockFactory);
        $this->assertEquals('App\test\Controller', $runner->getJobClass());
    }

    public function testGoLocked()
    {
        $lock = self::$lockFactory->createLock('cron.test.locked', 100);
        $lock->acquire();

        $job = new CronJob();
        $job->id = 'test.locked';
        $job->module = 'test';
        $job->command = 'locked';
        $job->setApp(Test::$app);
        $runner = new Runner($job, '', self::$lockFactory);

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
        $runner = new Runner($job, '', self::$lockFactory);

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
        $runner = new Runner($job, '', self::$lockFactory);

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
        $runner = new Runner($job, '', self::$lockFactory);

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
        $runner = new Runner($job, '', self::$lockFactory);

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
        $runner = new Runner($job, '', self::$lockFactory);

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
        $runner = new Runner($job, 'Infuse\Cron\Tests\TestJob', self::$lockFactory);

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
        $runner = new Runner($job, '', self::$lockFactory);

        FileGetContentsMock::$functions->shouldReceive('file_get_contents')
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
