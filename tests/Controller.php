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

use Infuse\HasApp;

class Controller
{
    use HasApp;

    public function exception()
    {
        throw new \Exception('test');
    }

    public function success()
    {
        echo 'test';

        return true;
    }

    public function success_with_url()
    {
        echo 'yay';

        return true;
    }
}
