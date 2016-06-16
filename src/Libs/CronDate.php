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

class CronDate
{
    /**
     * @staticvar array
     */
    private static $dateComponent = [
        'second' => 's',
        'minute' => 'i',
        'hour' => 'G',
        'day' => 'j',
        'month' => 'n',
        'year' => 'Y',
        'dow' => 'w',
        'timestamp' => 'U',
   ];

    /**
     * @staticvar array
     */
    private static $weekday = [
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
        0 => 'sunday',
   ];

    /**
     * @var DateParameters
     */
    private $dateParams;

    /**
     * @var int
     */
    private $start;

    /**
     * @var int
     */
    private $nextRun;

    /**
     * @param DateParameters $params date parameters
     * @param int            $start  start timestamp
     */
    public function __construct(DateParameters $params, $start = null)
    {
        $this->dateParams = $params;
        $this->start = is_null($start) ? time() : $start;
        $this->nextRun = $this->start;

        $this->calculate();
    }

    /**
     * Gets the cron date parameters.
     *
     * @return DateParameters
     */
    public function getDateParameters()
    {
        return $this->dateParams;
    }

    /**
     * Gets the start timestamp.
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Gets the calculated next run timestamp.
     *
     * @return int
     */
    public function getNextRun()
    {
        return $this->nextRun;
    }

    /**
     * Calculates the next run timestamp.
     */
    private function calculate()
    {
        $this->second = 0;

        $params = [
            'minute' => $this->dateParams->minute,
            'hour' => $this->dateParams->hour,
            'week' => $this->dateParams->week,
            'day' => $this->dateParams->day,
            'month' => $this->dateParams->month,
        ];

        if ($params['minute'] == '*') {
            $params['minute'] = null;
        }
        if ($params['hour'] == '*') {
            $params['hour'] = null;
        }
        if ($params['week'] == '*') {
            $params['week'] = null;
        }
        if ($params['day'] == '*') {
            $params['day'] = null;
        }
        if ($params['month'] == '*') {
            $params['month'] = null;
        }

        $done = 0;
        while ($done < 100) {
            if (!is_null($params['minute']) && ($this->minute != $params['minute'])) {
                if ($this->minute > $params['minute']) {
                    $this->modify('+1 hour');
                }
                $this->minute = $params['minute'];
            }
            if (!is_null($params['hour']) && ($this->hour != $params['hour'])) {
                if ($this->hour > $params['hour']) {
                    $this->modify('+1 day');
                }
                $this->hour = $params['hour'];
                $this->minute = 0;
            }
            if (!is_null($params['week']) && ($this->dow != $params['week'])) {
                $this->dow = $params['week'];
                $this->hour = 0;
                $this->minute = 0;
            }
            if (!is_null($params['day']) && ($this->day != $params['day'])) {
                if ($this->day > $params['day']) {
                    $this->modify('+1 month');
                }
                $this->day = $params['day'];
                $this->hour = 0;
                $this->minute = 0;
            }
            if (!is_null($params['month']) && ($this->month != $params['month'])) {
                if ($this->month > $params['month']) {
                    $this->modify('+1 year');
                }
                $this->month = $params['month'];
                $this->day = 1;
                $this->hour = 0;
                $this->minute = 0;
            }

            $done = (is_null($params['minute']) || $params['minute'] == $this->minute) &&
                    (is_null($params['hour']) || $params['hour'] == $this->hour) &&
                    (is_null($params['day']) || $params['day'] == $this->day) &&
                    (is_null($params['month']) || $params['month'] == $this->month) &&
                    (is_null($params['week']) || $params['week'] == $this->dow) ? 100 : ($done + 1);
        }
    }

    /**
     * Modifies the timestamp using PHP's strtotime().
     *
     * @param string $how date
     */
    private function modify($how)
    {
        $this->nextRun = strtotime($how, $this->nextRun);
    }

    public function __get($var)
    {
        return date(self::$dateComponent[$var], $this->nextRun);
    }

    public function __set($var, $value)
    {
        list($c['second'], $c['minute'], $c['hour'], $c['day'], $c['month'], $c['year'], $c['dow']) = explode(' ', date('s i G j n Y w', $this->nextRun));
        switch ($var) {
            case 'dow':
                $this->nextRun = strtotime(self::$weekday[$value], $this->nextRun);
                break;
            default:
                $c[$var] = $value;
                $this->nextRun = mktime($c['hour'], $c['minute'], $c['second'], $c['month'], $c['day'], $c['year']);
            }
    }
}
