<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Infuse\Cron\Libs;

use Symfony\Component\Console\Output\OutputInterface;

class Run
{
    const RESULT_SUCCEEDED = 'succeeded';
    const RESULT_LOCKED = 'locked';
    const RESULT_FAILED = 'failed';

    /**
     * @var array
     */
    private $output = [];

    /**
     * @var int
     */
    private $result;

    /**
     * @var OutputInterface
     */
    private $consoleOutput;

    /**
     * Writes output to the run.
     *
     * @param string $str
     *
     * @return self
     */
    public function writeOutput($str)
    {
        if (empty($str)) {
            return $this;
        }

        $this->output[] = $str;

        if ($this->consoleOutput) {
            $this->consoleOutput->writeln($str);
        }

        return $this;
    }

    /**
     * Gets the output from the run.
     *
     * @return string
     */
    public function getOutput()
    {
        return trim(implode("\n", $this->output));
    }

    /**
     * Sets the result of the run.
     *
     * @param int $result
     *
     * @return self
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Gets the result of the run.
     *
     * @return int
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Checks if the run succeeded.
     *
     * @return bool
     */
    public function succeeded()
    {
        return self::RESULT_SUCCEEDED == $this->result;
    }

    /**
     * Checks if the run failed.
     *
     * @return bool
     */
    public function failed()
    {
        return self::RESULT_FAILED == $this->result;
    }

    /**
     * Sets the console output.
     *
     * @param OutputInterface $output
     *
     * @return self
     */
    public function setConsoleOutput(OutputInterface $output)
    {
        $this->consoleOutput = $output;

        return $this;
    }
}
