<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace App\Test;

class Controller
{
    public function exception()
    {
        throw new \Exception('test');
    }

    public function success()
    {
        echo 'test';

        return true;
    }
}
