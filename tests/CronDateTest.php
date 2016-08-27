<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Cron\Libs\CronDate;
use Infuse\Cron\Libs\DateParameters;

class CronDateTest extends PHPUnit_Framework_TestCase
{
    public function testGetParams()
    {
        $params = new DateParameters();
        $date = new CronDate($params);
        $this->assertEquals($params, $date->getDateParameters());
    }

    public function testGetStart()
    {
        $params = new DateParameters();
        $date = new CronDate($params, 10);
        $this->assertEquals(10, $date->getStart());
    }

    public function testGetNextRunContinuousNow()
    {
        $params = new DateParameters();
        $start = mktime(0, 0, 0, 6, 16, 2016);
        $date = new CronDate($params, $start);

        // next run should be now
        $this->assertEquals($start, $date->getNextRun());
    }

    public function testGetNextRunMinuteNow()
    {
        $params = new DateParameters([
            'minute' => 1,
        ]);
        $start = mktime(0, 1, 0, 6, 16, 2016);
        $date = new CronDate($params, $start);

        // next run should be now
        $this->assertEquals($start, $date->getNextRun());
    }

    public function testGetNextRunNextMinute()
    {
        $params = new DateParameters([
            'minute' => 1,
        ]);
        $start = mktime(0, 2, 0, 6, 16, 2016);
        $date = new CronDate($params, $start);

        // next run should be the second minute on the next hour
        $expected = mktime(1, 1, 0, 6, 16, 2016);
        $this->assertEquals($expected, $date->getNextRun());
    }

    public function testGetNextRunHourNow()
    {
        $params = new DateParameters([
            'minute' => 0,
            'hour' => 1,
        ]);
        $start = mktime(1, 0, 0, 6, 16, 2016);
        $date = new CronDate($params, $start);

        // next run should be now
        $this->assertEquals($start, $date->getNextRun());
    }

    public function testGetNextRunNextHour()
    {
        $params = new DateParameters([
            'minute' => 0,
            'hour' => 1,
        ]);
        $start = mktime(2, 0, 0, 6, 16, 2016);
        $date = new CronDate($params, $start);

        // next run should be the first hour on the next day
        $expected = mktime(1, 0, 0, 6, 17, 2016);
        $this->assertEquals($expected, $date->getNextRun());
    }

    public function testGetNextRunDayOfWeekNow()
    {
        $params = new DateParameters([
            'minute' => 0,
            'hour' => 0,
            'week' => 1,
        ]);
        $start = mktime(0, 0, 0, 6, 20, 2016);
        $date = new CronDate($params, $start);

        // next run should be now
        $this->assertEquals($start, $date->getNextRun());
    }

    public function testGetNextRunNextDayOfWeek()
    {
        $params = new DateParameters([
            'minute' => 0,
            'hour' => 0,
            'week' => 1,
        ]);
        $start = mktime(0, 0, 0, 6, 16, 2016);
        $date = new CronDate($params, $start);

        // next run should be the next Monday
        $expected = mktime(0, 0, 0, 6, 20, 2016);
        $this->assertEquals($expected, $date->getNextRun());
    }

    public function testGetNextRunDayOfMonthNow()
    {
        $params = new DateParameters([
            'minute' => 0,
            'hour' => 0,
            'day' => 1,
        ]);
        $start = mktime(0, 0, 0, 7, 1, 2016);
        $date = new CronDate($params, $start);

        // next run should be the next first of the month
        $this->assertEquals($start, $date->getNextRun());
    }

    public function testGetNextRunNextDayOfMonth()
    {
        $params = new DateParameters([
            'minute' => 0,
            'hour' => 0,
            'day' => 1,
        ]);
        $start = mktime(0, 0, 0, 6, 16, 2016);
        $date = new CronDate($params, $start);

        // next run should be the next first of the month
        $expected = mktime(0, 0, 0, 7, 1, 2016);
        $this->assertEquals($expected, $date->getNextRun());
    }

    public function testGetNextRunMonthNow()
    {
        $params = new DateParameters([
            'minute' => 0,
            'hour' => 0,
            'day' => 1,
            'month' => 1,
        ]);
        $start = mktime(0, 0, 0, 1, 1, 2017);
        $date = new CronDate($params, $start);

        // next run should be the next January 1
        $this->assertEquals($start, $date->getNextRun());
    }

    public function testGetNextRunAll()
    {
        $params = new DateParameters([
            'minute' => 0,
            'hour' => 0,
            'week' => 1,
            'day' => 1,
            'month' => 1,
        ]);
        $start = mktime(0, 0, 0, 6, 16, 2016);
        $date = new CronDate($params, $start);

        // next run should be the next Monday
        // that is the first day of the
        // first month of the year, at midnight
        $expected = mktime(0, 0, 0, 1, 1, 2018);
        $this->assertEquals($expected, $date->getNextRun());
    }
}
