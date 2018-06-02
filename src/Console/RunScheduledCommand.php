<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Infuse\Cron\Console;

use Infuse\Cron\Libs\JobSchedule;
use Infuse\HasApp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunScheduledCommand extends Command
{
    use HasApp;

    protected function configure()
    {
        $this
            ->setName('cron:run')
            ->setDescription('Runs any scheduled jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->getSchedule()->runScheduled($output) ? 0 : 1;
    }

    /**
     * @return JobSchedule
     */
    private function getSchedule()
    {
        $jobs = (array) $this->app['config']->get('cron');
        $lockFactory = $this->app['lock_factory'];
        $namespace = $this->app['config']->get('app.hostname');
        $schedule = new JobSchedule($jobs, $lockFactory, $namespace);
        $this->addSubscribers($schedule);
        $schedule->setLogger($this->app['logger']);

        return $schedule;
    }

    /**
     * @param JobSchedule $schedule
     */
    private function addSubscribers(JobSchedule $schedule)
    {
        $subscribers = $this->app['config']->get('cronSubscribers', []);
        foreach ($subscribers as $class) {
            $subscriber = new $class();
            if (method_exists($subscriber, 'setApp')) {
                $subscriber->setApp($this->app);
            }

            $schedule->subscribe($subscriber);
        }
    }
}
