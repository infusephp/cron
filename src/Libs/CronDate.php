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
        'week' => 'w',
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
    private $params;

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
        $this->params = $params;
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
        return $this->params;
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

        $iters = 0;
        $maxIters = 100;
        while ($iters < $maxIters) {
            if ($this->params->minute != '*' && ($this->minute != $this->params->minute)) {
                if ($this->minute > $this->params->minute) {
                    $this->modify('+1 hour');
                }

                $this->minute = $this->params->minute;
            }

            if ($this->params->hour != '*' && ($this->hour != $this->params->hour)) {
                if ($this->hour > $this->params->hour) {
                    $this->modify('+1 day');
                }

                $this->hour = $this->params->hour;
                $this->minute = 0;
            }

            if ($this->params->week != '*' && ($this->week != $this->params->week)) {
                $this->week = $this->params->week;
                $this->hour = 0;
                $this->minute = 0;
            }

            if ($this->params->day != '*' && ($this->day != $this->params->day)) {
                if ($this->day > $this->params->day) {
                    $this->modify('+1 month');
                }

                $this->day = $this->params->day;
                $this->hour = 0;
                $this->minute = 0;
            }

            if ($this->params->month != '*' && ($this->month != $this->params->month)) {
                if ($this->month > $this->params->month) {
                    $this->modify('+1 year');
                }

                $this->month = $this->params->month;
                $this->day = 1;
                $this->hour = 0;
                $this->minute = 0;
            }

            ++$iters;

            if (($this->params->minute == '*' || $this->params->minute == $this->minute) &&
                ($this->params->hour == '*' || $this->params->hour == $this->hour) &&
                ($this->params->day == '*' || $this->params->day == $this->day) &&
                ($this->params->month == '*' || $this->params->month == $this->month) &&
                ($this->params->week == '*' || $this->params->week == $this->week)) {
                $iters = $maxIters;
            }
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
        list($c['second'], $c['minute'], $c['hour'], $c['day'], $c['month'], $c['year'], $c['week']) = explode(' ', date('s i G j n Y w', $this->nextRun));
        switch ($var) {
            case 'week':
                $this->nextRun = strtotime(self::$weekday[$value], $this->nextRun);
                break;
            default:
                $c[$var] = $value;
                $this->nextRun = mktime($c['hour'], $c['minute'], $c['second'], $c['month'], $c['day'], $c['year']);
            }
    }
}
