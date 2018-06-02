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
use Infuse\Cron\Tests\Jobs\ExceptionJob;
use Infuse\Cron\Tests\Jobs\FailJob;
use Infuse\Cron\Tests\Jobs\SuccessJob;
use Infuse\Cron\Tests\Jobs\SuccessWithUrlJob;
use Infuse\Cron\Tests\Jobs\TestJob;
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
        include_once 'file_get_contents_mock.php';

        Test::$app['database']->getDefault()
            ->delete('CronJobs')
            ->where('id', 'test%', 'like')
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
        $runner = new Runner($job, TestJob::class, self::$lockFactory);
        $this->assertEquals($job, $runner->getJobModel());
    }

    public function testGetJobClass()
    {
        $job = new CronJob();
        $runner = new Runner($job, TestJob::class, self::$lockFactory);
        $this->assertEquals(TestJob::class, $runner->getJobClass());
    }

    public function testGoLocked()
    {
        $lock = self::$lockFactory->createLock('cron.test.locked', 100);
        $lock->acquire();

        $job = new CronJob();
        $job->id = 'test.locked';
        $job->setApp(Test::$app);
        $runner = new Runner($job, TestJob::class, self::$lockFactory);

        $run = $runner->go(100);
        $this->assertInstanceOf(Run::class, $run);
        $this->assertEquals(Run::RESULT_LOCKED, $run->getResult());

        $this->assertFalse($job->persisted());
    }

    public function testGoClassMissing()
    {
        $job = new CronJob();
        $job->id = 'test.class_missing';
        $runner = new Runner($job, '', self::$lockFactory);

        $run = $runner->go();
        $this->assertInstanceOf(Run::class, $run);
        $this->assertEquals(Run::RESULT_FAILED, $run->getResult());

        $this->assertTrue($job->persisted());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_succeeded);
        $this->assertEquals('Missing `class` parameter on test.class_missing job', $job->last_run_output);
    }

    public function testGoClassDoesNotExist()
    {
        $job = new CronJob();
        $job->id = 'test.does_not_exist';
        $runner = new Runner($job, 'DoesNotExist\MyJob', self::$lockFactory);

        $run = $runner->go();
        $this->assertInstanceOf(Run::class, $run);
        $this->assertEquals(Run::RESULT_FAILED, $run->getResult());

        $this->assertTrue($job->persisted());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_succeeded);
        $this->assertEquals('DoesNotExist\MyJob does not exist', $job->last_run_output);
    }

    public function testGoException()
    {
        $job = new CronJob();
        $job->id = 'test.exception';
        $runner = new Runner($job, ExceptionJob::class, self::$lockFactory);

        $run = $runner->go();
        $this->assertInstanceOf(Run::class, $run);
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
        $runner = new Runner($job, FailJob::class, self::$lockFactory);

        $run = $runner->go();
        $this->assertInstanceOf(Run::class, $run);
        $this->assertEquals(Run::RESULT_FAILED, $run->getResult());

        $this->assertTrue($job->persisted());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertFalse($job->last_run_succeeded);
    }

    public function testGoSuccess()
    {
        $job = new CronJob();
        $job->id = 'test.success';
        $runner = new Runner($job, SuccessJob::class, self::$lockFactory);

        $run = $runner->go();
        $this->assertInstanceOf(Run::class, $run);
        $this->assertEquals(Run::RESULT_SUCCEEDED, $run->getResult());

        $this->assertTrue($job->persisted());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertTrue($job->last_run_succeeded);
        $this->assertEquals("test run obj\ntest", $job->last_run_output);
    }

    public function testGoSuccessNoReturnValue()
    {
        $job = new CronJob();
        $job->id = 'test.invoke';
        $runner = new Runner($job, TestJob::class, self::$lockFactory);

        $run = $runner->go();
        $this->assertInstanceOf(Run::class, $run);
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
        $runner = new Runner($job, SuccessWithUrlJob::class, self::$lockFactory);

        FileGetContentsMock::$functions->shouldReceive('file_get_contents')
                        ->with('http://webhook.example.com/?m=yay')
                        ->once();

        $run = $runner->go(0, 'http://webhook.example.com/');
        $this->assertInstanceOf(Run::class, $run);
        $this->assertEquals(Run::RESULT_SUCCEEDED, $run->getResult());

        $this->assertTrue($job->persisted());
        $this->assertGreaterThan(0, $job->last_ran);
        $this->assertTrue($job->last_run_succeeded);
        $this->assertEquals('yay', $job->last_run_output);
    }
}
