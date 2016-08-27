<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Cron\Libs\DateParameters;

class DateParametersTest extends PHPUnit_Framework_TestCase
{
    public function testDefault()
    {
        $date = new DateParameters();
        $this->assertEquals('*', $date->minute);
        $this->assertEquals('*', $date->hour);
        $this->assertEquals('*', $date->week);
        $this->assertEquals('*', $date->day);
        $this->assertEquals('*', $date->month);
    }

    public function testWithSetting()
    {
        $date = new DateParameters(['minute' => 10, 'hour' => '*']);
        $this->assertEquals(10, $date->minute);
        $this->assertEquals('*', $date->hour);
        $this->assertEquals('*', $date->week);
        $this->assertEquals('*', $date->day);
        $this->assertEquals('*', $date->month);
    }

    public function testInvalidMinute()
    {
        $this->setExpectedException('InvalidArgumentException');
        $date = new DateParameters(['minute' => 60]);
    }

    public function testInvalidHour()
    {
        $this->setExpectedException('InvalidArgumentException');
        $date = new DateParameters(['hour' => 24]);
    }

    public function testInvalidDayOfWeek()
    {
        $this->setExpectedException('InvalidArgumentException');
        $date = new DateParameters(['week' => 7]);
    }

    public function testInvalidDayOfMonth()
    {
        $this->setExpectedException('InvalidArgumentException');
        $date = new DateParameters(['day' => 32]);
    }

    public function testInvalidMonth()
    {
        $this->setExpectedException('InvalidArgumentException');
        $date = new DateParameters(['month' => 13]);
    }
}
