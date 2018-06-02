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

use Infuse\Cron\Libs\Lock;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

class LockTest extends MockeryTestCase
{
    public static $lockFactory;

    public static function setUpBeforeClass()
    {
        $store = new FlockStore(sys_get_temp_dir());
        self::$lockFactory = new Factory($store);
    }

    public function testGetName()
    {
        $lock = new Lock('module.command', self::$lockFactory);
        $this->assertEquals('cron.module.command', $lock->getName());
        $lock = new Lock('module.command', self::$lockFactory, 'namespaced');
        $this->assertEquals('namespaced:cron.module.command', $lock->getName());
    }

    public function testAcquireNoExpiry()
    {
        $lock = new Lock('module.command', self::$lockFactory);

        $this->assertFalse($lock->hasLock());
        $this->assertTrue($lock->acquire(0));
        $this->assertFalse($lock->hasLock());
    }

    public function testAcquire()
    {
        $lock = new Lock('module.command', self::$lockFactory);
        $this->assertTrue($lock->acquire(100));
        $this->assertTrue($lock->hasLock());

        $lock->release();
        $this->assertFalse($lock->hasLock());
    }

    public function testAcquireNamespace()
    {
        $lock1 = new Lock('test', self::$lockFactory);
        $this->assertTrue($lock1->acquire(100));

        $lock2 = new Lock('test', self::$lockFactory, 'namespaced');
        $this->assertTrue($lock2->acquire(100));
        $this->assertTrue($lock1->hasLock());
        $this->assertTrue($lock2->hasLock());
    }
}
