<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Infuse\Cron\Tests;

use Infuse\Cron\Libs\DateParameters;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DateParametersTest extends MockeryTestCase
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
        $this->expectException('InvalidArgumentException');
        $date = new DateParameters(['minute' => 60]);
    }

    public function testInvalidHour()
    {
        $this->expectException('InvalidArgumentException');
        $date = new DateParameters(['hour' => 24]);
    }

    public function testInvalidDayOfWeek()
    {
        $this->expectException('InvalidArgumentException');
        $date = new DateParameters(['week' => 7]);
    }

    public function testInvalidDayOfMonth()
    {
        $this->expectException('InvalidArgumentException');
        $date = new DateParameters(['day' => 32]);
    }

    public function testInvalidMonth()
    {
        $this->expectException('InvalidArgumentException');
        $date = new DateParameters(['month' => 13]);
    }
}
