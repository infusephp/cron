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

use Infuse\HasApp;

class Lock
{
    use HasApp;

    /**
     * @var string
     */
    private $jobId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $hasLock;

    /**
     * @var string
     */
    public function __construct($jobId)
    {
        $this->jobId = $jobId;
        $this->hasLock = false;
    }

    /**
     * Checks if this instance has the lock.
     *
     * @return bool
     */
    public function hasLock()
    {
        return $this->hasLock;
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

        $redis = $this->getRedis();
        $k = $this->getName();

        if ($redis->setnx($k, $expires)) {
            $redis->expire($k, $expires);

            $this->hasLock = true;

            return true;
        }

        return false;
    }

    /**
     * Releases the lock.
     *
     * @return self
     */
    public function release()
    {
        if (!$this->hasLock) {
            return $this;
        }

        $this->getRedis()->del($this->getName());

        $this->hasLock = false;

        return $this;
    }

    /**
     * Gets the name of the global lock for this job.
     *
     * @return string
     */
    public function getName()
    {
        if (!$this->name) {
            // namespace
            $this->name = $this->app['config']->get('app.hostname').':';
            // key
            $this->name .= 'cron.'.$this->jobId;
        }

        return $this->name;
    }

    /**
     * Gets the redis instance.
     *
     * @return \Redis
     */
    private function getRedis()
    {
        return $this->app['redis'];
    }
}
