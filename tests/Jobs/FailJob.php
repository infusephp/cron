<?php

namespace Infuse\Cron\Tests\Jobs;

use Infuse\Cron\Libs\Run;

class FailJob
{
    public function __invoke(Run $run)
    {
        return false;
    }
}
