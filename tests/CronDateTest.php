<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use App\Cron\Libs\CronDate;
use App\Cron\Libs\DateParameters;

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

    public function testGetNextRunMinuteNow()
    {
        $params = new DateParameters();
        $start = mktime(0, 0, 0, 6, 16, 2016);
        $date = new CronDate($params, $start);

        // next run should be now
        $this->assertEquals($start, $date->getNextRun());
    }

    public function testGetNextRunMinuteNext()
    {
        $params = new DateParameters();
        $start = mktime(0, 0, 1, 6, 16, 2016);
        $date = new CronDate($params, $start);

        // next run should be the following minute
        $expected = mktime(0, 0, 0, 6, 16, 2016);
        $this->assertEquals($expected, $date->getNextRun());
    }

    public function testGetNextRunDayOfWeek()
    {
        $params = new DateParameters([
            'minute' => 0,
            'hour' => 0,
            'week' => 1,
            'day' => 1,
        ]);
        $start = mktime(0, 0, 0, 6, 16, 2016);
        $date = new CronDate($params, $start);

        // next run should be the next Monday
        // that is the first day of the month
        $expected = mktime(0, 0, 0, 8, 1, 2016);
        $this->assertEquals($expected, $date->getNextRun());
    }
}
