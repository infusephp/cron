<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace App\Cron\Console;

use App\Cron\Libs\JobSchedule;
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
            ->setName('run-scheduled')
            ->setDescription('Runs any scheduled jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobs = (array) $this->app['config']->get('cron');
        $schedule = new JobSchedule($jobs);

        $scheduleOutput = '';
        $result = $schedule->run($scheduleOutput);

        foreach (explode("\n", $scheduleOutput) as $line) {
            $output->writeln($line);
        }

        return $result;
    }
}
