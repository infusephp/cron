<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use App\Cron\Libs\Run;

class RunTest extends PHPUnit_Framework_TestCase
{
    public function testWriteOutput()
    {
        $run = new Run();
        $this->assertEquals('', $run->getOutput());
        $run->writeOutput('1');
        $run->writeOutput('2');
        $run->writeOutput('3');
        $this->assertEquals("1\n2\n3", $run->getOutput());
    }

    public function testWriteOutputWithConsole()
    {
        $console = Mockery::mock('Symfony\Component\Console\Output\OutputInterface');
        $console->shouldReceive('writeln')
                ->times(3);

        $run = new Run();
        $run->setConsoleOutput($console);
        $run->writeOutput('1');
        $run->writeOutput('2');
        $run->writeOutput('3');
        $this->assertEquals("1\n2\n3", $run->getOutput());
    }

    public function testGetResult()
    {
        $run = new Run();
        $run->setResult(1);
        $this->assertEquals(1, $run->getResult());
    }

    public function testSucceeded()
    {
        $run = new Run();
        $this->assertFalse($run->succeeded());
        $run->setResult(Run::RESULT_SUCCEEDED);
        $this->assertTrue($run->succeeded());
    }

    public function testFailed()
    {
        $run = new Run();
        $this->assertFalse($run->failed());
        $run->setResult(Run::RESULT_FAILED);
        $this->assertTrue($run->failed());
    }
}
