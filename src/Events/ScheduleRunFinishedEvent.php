<?php

namespace Infuse\Cron\Events;

use Symfony\Contracts\EventDispatcher\Event;

class ScheduleRunFinishedEvent extends Event
{
    const NAME = 'schedule_run.finished';
}
