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
    ///////////////////////////
    // Private Class Variables
    ///////////////////////////

    private $myTimestamp;
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
     * Constructor.
     *
     * @param int $timestamp timestamp
     */
    public function __construct($timestamp = null)
    {
        $this->myTimestamp = is_null($timestamp) ? time() : $timestamp;
    }

    /**
     * Getter.
     *
     * @param string $var value to get
     *
     * @return mixed value
     */
    public function __get($var)
    {
        return date(self::$dateComponent[$var], $this->myTimestamp);
    }

    /**
     * Setter.
     *
     * @param string $var   type of set to perform
     * @param mixed  $value value to set
     */
    public function __set($var, $value)
    {
        list($c['second'], $c['minute'], $c['hour'], $c['day'], $c['month'], $c['year'], $c['dow']) = explode(' ', date('s i G j n Y w', $this->myTimestamp));
        switch ($var) {
            case 'dow':
                $this->myTimestamp = strtotime(self::$weekday[$value], $this->myTimestamp);
                break;
            case 'timestamp':
                $this->myTimestamp = $value;
                break;
            default:
                $c[$var] = $value;
                $this->myTimestamp = mktime($c['hour'], $c['minute'], $c['second'], $c['month'], $c['day'], $c['year']);
            }
    }

    /**
     * Modifies the timestamp using PHP's strtotime().
     *
     * @param string $how date
     *
     * @return bool success
     */
    public function modify($how)
    {
        return $this->myTimestamp = strtotime($how, $this->myTimestamp);
    }
}
