<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Infuse\Cron\Libs;

use Symfony\Component\Lock\Factory;

class Lock
{
    /**
     * @var string
     */
    private $jobId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var \Symfony\Component\Lock\Lock
     */
    private $lock;

    /**
     * @param string  $jobId
     * @param Factory $lockFactory
     * @param string  $namespace
     */
    public function __construct($jobId, Factory $lockFactory, $namespace = '')
    {
        $this->jobId = $jobId;
        if ($namespace) {
            $this->name = $namespace.':cron.'.$this->jobId;
        } else {
            $this->name = 'cron.'.$this->jobId;
        }
        $this->factory = $lockFactory;
    }

    /**
     * Checks if this instance has the lock.
     *
     * @return bool
     */
    public function hasLock()
    {
        return $this->lock ? $this->lock->isAcquired() : false;
    }

    /**
     * Attempts to acquire the global lock for this job.
     *
     * @param int $expires time in seconds after which the lock expires
     *
     * @return bool
     */
    public function acquire($expires)
    {
        // do not lock if expiry time is 0
        if ($expires <= 0) {
            return true;
        }

        $k = $this->getName();
        $this->lock = $this->factory->createLock($k, $expires);

        return $this->lock->acquire();
    }

    /**
     * Releases the lock.
     *
     * @return self
     */
    public function release()
    {
        if (!$this->lock) {
            return $this;
        }

        $this->lock->release();

        return $this;
    }

    /**
     * Gets the name of the global lock for this job.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
