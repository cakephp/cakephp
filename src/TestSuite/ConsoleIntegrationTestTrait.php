<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.7.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Console\Command;
use Cake\Console\CommandRunner;
use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\Core\Configure;
use Cake\TestSuite\Constraint\Console\ContentsContain;
use Cake\TestSuite\Constraint\Console\ContentsContainRow;
use Cake\TestSuite\Constraint\Console\ContentsEmpty;
use Cake\TestSuite\Constraint\Console\ContentsNotContain;
use Cake\TestSuite\Constraint\Console\ContentsRegExp;
use Cake\TestSuite\Constraint\Console\ExitCode;
use Cake\TestSuite\Stub\ConsoleInput;
use Cake\TestSuite\Stub\ConsoleOutput;

/**
 * A test case class intended to make integration tests of cake console commands
 * easier.
 */
trait ConsoleIntegrationTestTrait
{
    /**
     * Whether or not to use the CommandRunner
     *
     * @var bool
     */
    protected $_useCommandRunner = false;

    /**
     * Last exit code
     *
     * @var int|null
     */
    protected $_exitCode;

    /**
     * Console output stub
     *
     * @var \Cake\TestSuite\Stub\ConsoleOutput|\PHPUnit_Framework_MockObject_MockObject|null
     */
    protected $_out;

    /**
     * Console error output stub
     *
     * @var \Cake\TestSuite\Stub\ConsoleOutput|\PHPUnit_Framework_MockObject_MockObject|null
     */
    protected $_err;

    /**
     * Console input mock
     *
     * @var \Cake\Console\ConsoleInput|\PHPUnit_Framework_MockObject_MockObject|null
     */
    protected $_in;

    /**
     * Runs cli integration test
     *
     * @param string $command Command to run
     * @param array $input Input values to pass to an interactive shell
     * @return void
     */
    public function exec($command, array $input = [])
    {
        $runner = $this->makeRunner();

        $this->_out = new ConsoleOutput();
        $this->_err = new ConsoleOutput();
        $this->_in = new ConsoleInput($input);

        $args = $this->commandStringToArgs("cake $command");
        $io = new ConsoleIo($this->_out, $this->_err, $this->_in);

        try {
            $this->_exitCode = $runner->run($args, $io);
        } catch (StopException $exception) {
            $this->_exitCode = $exception->getCode();
        }
    }

    /**
     * Cleans state to get ready for the next test
     *
     * @after
     * @return void
     */
    public function cleanupConsoleTrait()
    {
        $this->_exitCode = null;
        $this->_out = null;
        $this->_err = null;
        $this->_in = null;
        $this->_useCommandRunner = false;
    }

    /**
     * Set this test case to use the CommandRunner rather than the legacy
     * ShellDispatcher
     *
     * @return void
     */
    public function useCommandRunner()
    {
        $this->_useCommandRunner = true;
    }

    /**
     * Asserts shell exited with the expected code
     *
     * @param int $expected Expected exit code
     * @param string $message Failure message
     * @return void
     */
    public function assertExitCode($expected, $message = '')
    {
        $this->assertThat($expected, new ExitCode($this->_exitCode), $message);
    }

    /**
     * Asserts shell exited with the Command::CODE_SUCCESS
     *
     * @param string $message Failure message
     * @return void
     */
    public function assertExitSuccess($message = '')
    {
        $this->assertThat(Command::CODE_SUCCESS, new ExitCode($this->_exitCode), $message);
    }

    /**
     * Asserts shell exited with Command::CODE_ERROR
     *
     * @param string $message Failure message
     * @return void
     */
    public function assertExitError($message = '')
    {
        $this->assertThat(Command::CODE_ERROR, new ExitCode($this->_exitCode), $message);
    }

    /**
     * Asserts that `stdout` is empty
     *
     * @param string $message The message to output when the assertion fails.
     * @return void
     */
    public function assertOutputEmpty($message = '')
    {
        $this->assertThat(null, new ContentsEmpty($this->_out->messages(), 'output'), $message);
    }

    /**
     * Asserts `stdout` contains expected output
     *
     * @param string $expected Expected output
     * @param string $message Failure message
     * @return void
     */
    public function assertOutputContains($expected, $message = '')
    {
        $this->assertThat($expected, new ContentsContain($this->_out->messages(), 'output'), $message);
    }

    /**
     * Asserts `stdout` does not contain expected output
     *
     * @param string $expected Expected output
     * @param string $message Failure message
     * @return void
     */
    public function assertOutputNotContains($expected, $message = '')
    {
        $this->assertThat($expected, new ContentsNotContain($this->_out->messages(), 'output'), $message);
    }

    /**
     * Asserts `stdout` contains expected regexp
     *
     * @param string $pattern Expected pattern
     * @param string $message Failure message
     * @return void
     */
    public function assertOutputRegExp($pattern, $message = '')
    {
        $this->assertThat($pattern, new ContentsRegExp($this->_out->messages(), 'output'), $message);
    }

    /**
     * Check that a row of cells exists in the output.
     *
     * @param array $row Row of cells to ensure exist in the output.
     * @param string $message Failure message.
     * @return void
     */
    protected function assertOutputContainsRow(array $row, $message = '')
    {
        $this->assertThat($row, new ContentsContainRow($this->_out->messages(), 'output'), $message);
    }

    /**
     * Asserts `stderr` contains expected output
     *
     * @param string $expected Expected output
     * @param string $message Failure message
     * @return void
     */
    public function assertErrorContains($expected, $message = '')
    {
        $this->assertThat($expected, new ContentsContain($this->_err->messages(), 'error output'), $message);
    }

    /**
     * Asserts `stderr` contains expected regexp
     *
     * @param string $pattern Expected pattern
     * @param string $message Failure message
     * @return void
     */
    public function assertErrorRegExp($pattern, $message = '')
    {
        $this->assertThat($pattern, new ContentsRegExp($this->_err->messages(), 'error output'), $message);
    }

    /**
     * Asserts that `stderr` is empty
     *
     * @param string $message The message to output when the assertion fails.
     * @return void
     */
    public function assertErrorEmpty($message = '')
    {
        $this->assertThat(null, new ContentsEmpty($this->_err->messages(), 'error output'), $message);
    }

    /**
     * Builds the appropriate command dispatcher
     *
     * @return CommandRunner|LegacyCommandRunner
     */
    protected function makeRunner()
    {
        if ($this->_useCommandRunner) {
            $applicationClassName = Configure::read('App.namespace') . '\Application';

            return new CommandRunner(new $applicationClassName(CONFIG));
        }

        return new LegacyCommandRunner();
    }

    /**
     * Creates an $argv array from a command string
     *
     * @param string $command Command string
     * @return array
     */
    protected function commandStringToArgs($command)
    {
        $charCount = strlen($command);
        $argv = [];
        $arg = '';
        $inDQuote = false;
        $inSQuote = false;
        for ($i = 0; $i < $charCount; $i++) {
            $char = substr($command, $i, 1);

            // end of argument
            if ($char === ' ' && !$inDQuote && !$inSQuote) {
                if (strlen($arg)) {
                    $argv[] = $arg;
                }
                $arg = '';
                continue;
            }

            // exiting single quote
            if ($inSQuote && $char === "'") {
                $inSQuote = false;
                continue;
            }

            // exiting double quote
            if ($inDQuote && $char === '"') {
                $inDQuote = false;
                continue;
            }

            // entering double quote
            if ($char === '"' && !$inSQuote) {
                $inDQuote = true;
                continue;
            }

            // entering single quote
            if ($char === "'" && !$inDQuote) {
                $inSQuote = true;
                continue;
            }

            $arg .= $char;
        }
        $argv[] = $arg;

        return $argv;
    }
}
