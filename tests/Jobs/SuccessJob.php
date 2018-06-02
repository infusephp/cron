<?php

namespace Infuse\Cron\Tests\Jobs;

use Infuse\Cron\Libs\Run;

class SuccessJob
{
    public function __invoke(Run $run)
    {
        echo 'test';

        $run->writeOutput('test run obj');

        return true;
    }
}
