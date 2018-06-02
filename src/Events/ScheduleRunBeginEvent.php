<?php

namespace Infuse\Cron\Events;

use Symfony\Component\EventDispatcher\Event;

class ScheduleRunBeginEvent extends Event
{
    const NAME = 'schedule_run.begin';
}
