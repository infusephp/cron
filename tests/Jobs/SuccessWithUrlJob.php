<?php

namespace Infuse\Cron\Tests\Jobs;

use Infuse\Cron\Libs\Run;

class SuccessWithUrlJob
{
    public function __invoke(Run $run)
    {
        echo 'yay';
    }
}
