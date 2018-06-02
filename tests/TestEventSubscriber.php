<?php

namespace Infuse\Cron\Tests;

use Infuse\Cron\Events\ScheduleRunBeginEvent;
use Infuse\Cron\Events\ScheduleRunFinishedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestEventSubscriber implements EventSubscriberInterface
{
    public static $lastEvent;

    public static function getSubscribedEvents()
    {
        return [
            ScheduleRunBeginEvent::NAME => 'runBegin',
            ScheduleRunFinishedEvent::NAME => 'runFinished',
        ];
    }

    public function runBegin(ScheduleRunBeginEvent $event)
    {
        self::$lastEvent = $event;
    }

    public function runFinished(ScheduleRunFinishedEvent $event)
    {
        self::$lastEvent = $event;
    }
}
