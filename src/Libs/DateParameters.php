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

use InvalidArgumentException;

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

        $this->validate();
    }

    public function __get($k)
    {
        return $this->params[$k];
    }

    private function validate()
    {
        if ($this->params['minute'] !== '*' && !($this->params['minute'] >= 0 && $this->params['minute'] <= 59)) {
            throw new InvalidArgumentException("Invalid minute argument: {$this->params['minute']}");
        }

        if ($this->params['hour'] !== '*' && !($this->params['hour'] >= 0 && $this->params['hour'] <= 23)) {
            throw new InvalidArgumentException("Invalid hour argument: {$this->params['hour']}");
        }

        if ($this->params['week'] !== '*' && !($this->params['week'] >= 0 && $this->params['week'] <= 6)) {
            throw new InvalidArgumentException("Invalid day of week argument: {$this->params['week']}");
        }

        if ($this->params['day'] !== '*' && !($this->params['day'] >= 1 && $this->params['day'] <= 31)) {
            throw new InvalidArgumentException("Invalid day of month argument: {$this->params['day']}");
        }

        if ($this->params['month'] !== '*' && !($this->params['month'] >= 1 && $this->params['month'] <= 12)) {
            throw new InvalidArgumentException("Invalid month argument: {$this->params['month']}");
        }
    }
}
