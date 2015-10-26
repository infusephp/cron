<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace app\cron\console;

use app\cron\libs\Cron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunScheduledCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('run-scheduled')
            ->setDescription('Runs any scheduled cron jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cronOutput = '';
        $result = Cron::scheduleCheck($cronOutput);

        foreach (explode("\n", $cronOutput) as $line) {
            $output->writeln($line);
        }

        return $result;
    }
}
