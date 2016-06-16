<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use App\Cron\Libs\Lock;
use Infuse\Application;

class LockTest extends \PHPUnit_Framework_TestCase
{
    public function testGetLockNoExpiry()
    {
        $lock = new Lock('module', 'command');

        $this->assertFalse($lock->hasLock());
        $this->assertTrue($lock->getLock(0));
        $this->assertFalse($lock->hasLock());
    }

    public function testGetLock()
    {
        $app = new Application();
        $app['config']->set('app.hostname', 'example.com');
        $redis = Mockery::mock();
        $redis->shouldReceive('setnx')->withArgs(['example.com:cron.module.command', 100])->andReturn(true)->once();
        $redis->shouldReceive('del')->withArgs(['example.com:cron.module.command'])->andReturn(true)->once();
        $redis->shouldReceive('expire')->withArgs(['example.com:cron.module.command', 100])->andReturn(true)->once();
        $app['redis'] = $redis;

        $lock = new Lock('module', 'command');
        $lock->setApp($app);
        $this->assertTrue($lock->getLock(100));
        $this->assertTrue($lock->hasLock());

        $lock->releaseLock();
        $this->assertFalse($lock->hasLock());
    }
}
