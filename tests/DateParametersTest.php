<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use App\Cron\Libs\DateParameters;

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
        $date = new DateParameters(['minute' => 10]);
        $this->assertEquals(10, $date->minute);
        $this->assertEquals('*', $date->hour);
        $this->assertEquals('*', $date->week);
        $this->assertEquals('*', $date->day);
        $this->assertEquals('*', $date->month);
    }
}
