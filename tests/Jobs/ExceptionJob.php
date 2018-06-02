<?php

namespace Infuse\Cron\Tests\Jobs;

use Infuse\Cron\Libs\Run;

class ExceptionJob
{
    public function __invoke(Run $run)
    {
        throw new \Exception('test');
    }
}
