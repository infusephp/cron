<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Infuse\Cron\Tests\Jobs;

use Infuse\Cron\Libs\Run;
use Infuse\HasApp;

class TestJob
{
    use HasApp;

    public function __invoke(Run $run)
    {
        $run->writeOutput('works');
    }
}
