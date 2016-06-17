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

class TestJob
{
    use HasApp;

    public function __invoke(Run $run)
    {
        $run->writeOutput('works');
    }
}
