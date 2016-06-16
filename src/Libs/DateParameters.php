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

class DateParameters
{
    /**
     * @var array
     */
    private $params;

    /**
     * @param array $params cron date parameters (minute, hour, day, month, week)
     */
    public function __construct(array $params = [])
    {
        $this->params = array_replace([
            'minute' => '*',
            'hour' => '*',
            'week' => '*',
            'day' => '*',
            'month' => '*',
        ], $params);
    }

    public function __get($k)
    {
        return $this->params[$k];
    }
}
