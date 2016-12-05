<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
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
        $jobs = (array) $this->app['config']->get('cron');
        $schedule = new JobSchedule($jobs);

        return $schedule->runScheduled($output) ? 0 : 1;
    }
}
