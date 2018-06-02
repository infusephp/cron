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

use Infuse\Cron\Events\ScheduleRunBeginEvent;
use Infuse\Cron\Events\ScheduleRunFinishedEvent;
use Infuse\Cron\Libs\JobSchedule;
use Infuse\Cron\Models\CronJob;
use Infuse\Cron\Tests\Jobs\FailJob;
use Infuse\Cron\Tests\Jobs\SuccessJob;
use Infuse\Cron\Tests\Jobs\SuccessWithUrlJob;
use Infuse\Test;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

class JobScheduleTest extends MockeryTestCase
{
    public static $jobs = [
        [
            'id' => 'test.success_with_url',
            'class' => SuccessWithUrlJob::class,
            'minute' => 0,
            'hour' => 0,
        ],
        [
            'id' => 'test.success',
            'class' => SuccessJob::class,
        ],
        [
            'id' => 'test.locked',
            'class' => SuccessJob::class,
            'expires' => 100,
        ],
        [
            'id' => 'test.failed',
            'class' => FailJob::class,
        ],
    ];
    public static $lockFactory;
    public static $beginEvent;
    public static $finishedEvent;

    public static function setUpBeforeClass()
    {
        Test::$app['database']->getDefault()
            ->delete('CronJobs')
            ->where('id', 'test%', 'like')
            ->execute();

        $store = new FlockStore(sys_get_temp_dir());
        self::$lockFactory = new Factory($store);

        $lock = self::$lockFactory->createLock('cron.test.locked', 100);
        $lock->acquire();
    }

    private function getSchedule()
    {
        return new JobSchedule(self::$jobs, self::$lockFactory);
    }

    public function testGetAllJobs()
    {
        $this->assertEquals(self::$jobs, $this->getSchedule()->getAllJobs());
    }

    public function testGetScheduledJobs()
    {
        $jobs = $this->getSchedule()->getScheduledJobs();

        $this->assertCount(4, $jobs);

        $this->assertInstanceOf(CronJob::class, $jobs[0]['model']);
        $this->assertEquals('test.success_with_url', $jobs[0]['model']->id);

        $this->assertInstanceOf(CronJob::class, $jobs[1]['model']);
        $this->assertEquals('test.success', $jobs[1]['model']->id);

        $this->assertInstanceOf(CronJob::class, $jobs[2]['model']);
        $this->assertEquals('test.locked', $jobs[2]['model']->id);

        $this->assertInstanceOf(CronJob::class, $jobs[3]['model']);
        $this->assertEquals('test.failed', $jobs[3]['model']->id);
    }

    public function testRunScheduled()
    {
        $output = Mockery::mock('Symfony\Component\Console\Output\OutputInterface');
        $output->shouldReceive('writeln')
            ->atLeast(1);

        $schedule = $this->getSchedule();
        $schedule->listen(ScheduleRunBeginEvent::NAME, function (ScheduleRunBeginEvent $event) {
            JobScheduleTest::$beginEvent = $event;
        });
        $schedule->listen(ScheduleRunFinishedEvent::NAME, function (ScheduleRunFinishedEvent $event) {
            JobScheduleTest::$finishedEvent = $event;
        });

        $subscriber = new TestEventSubscriber();
        $schedule->subscribe($subscriber);

        $this->assertFalse($schedule->runScheduled($output));

        // running the schedule should remove the
        // `success_with_url` job from the schedule
        $jobs = $schedule->getScheduledJobs();
        $this->assertCount(3, $jobs);
        $this->assertEquals('test.success', $jobs[0]['model']->id);
        $this->assertEquals('test.locked', $jobs[1]['model']->id);
        $this->assertEquals('test.failed', $jobs[2]['model']->id);

        $this->assertInstanceOf(ScheduleRunBeginEvent::class, self::$beginEvent);
        $this->assertInstanceOf(ScheduleRunFinishedEvent::class, self::$finishedEvent);
    }
}
