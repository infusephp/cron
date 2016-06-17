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

use App\Cron\Libs\Run;
use Infuse\HasApp;

class Controller
{
    use HasApp;

    public function exception()
    {
        throw new \Exception('test');
    }

    public function success(Run $run)
    {
        echo 'test';

        $run->writeOutput('test run obj');

        return true;
    }

    public function success_with_url(Run $run)
    {
        echo 'yay';
    }

    public function fail()
    {
        return false;
    }
}
