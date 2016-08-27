<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Infuse\Cron\Libs;

use Symfony\Component\Console\Output\OutputInterface;

class Run
{
    const RESULT_SUCCEEDED = 1;
    const RESULT_LOCKED = 2;
    const RESULT_FAILED = 3;

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
        return $this->result == self::RESULT_SUCCEEDED;
    }

    /**
     * Checks if the run failed.
     *
     * @return bool
     */
    public function failed()
    {
        return $this->result == self::RESULT_FAILED;
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
