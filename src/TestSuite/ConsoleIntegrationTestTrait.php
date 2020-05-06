<?php
declare(strict_types=1);

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

use Cake\Command\Command;
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
use Cake\TestSuite\Stub\MissingConsoleInputException;
use RuntimeException;

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
     * The customized application class name.
     *
     * @var string|null
     * @psalm-var class-string<\Cake\Core\ConsoleApplicationInterface>|null
     */
    protected $_appClass;

    /**
     * The customized application constructor arguments.
     *
     * @var array|null
     */
    protected $_appArgs;

    /**
     * Last exit code
     *
     * @var int|null
     */
    protected $_exitCode;

    /**
     * Console output stub
     *
     * @var \Cake\TestSuite\Stub\ConsoleOutput
     */
    protected $_out;

    /**
     * Console error output stub
     *
     * @var \Cake\TestSuite\Stub\ConsoleOutput
     */
    protected $_err;

    /**
     * Console input mock
     *
     * @var \Cake\Console\ConsoleInput
     */
    protected $_in;

    /**
     * Runs cli integration test
     *
     * @param string $command Command to run
     * @param array $input Input values to pass to an interactive shell
     * @throws \Cake\TestSuite\Stub\MissingConsoleInputException
     * @throws \RuntimeException
     * @return void
     */
    public function exec(string $command, array $input = []): void
    {
        $runner = $this->makeRunner();

        if ($this->_out === null) {
            $this->_out = new ConsoleOutput();
        }
        if ($this->_err === null) {
            $this->_err = new ConsoleOutput();
        }
        if ($this->_in === null) {
            $this->_in = new ConsoleInput($input);
        } elseif ($input) {
            throw new RuntimeException('You can use `$input` only if `$_in` property is null and will be reset.');
        }

        $args = $this->commandStringToArgs("cake $command");
        $io = new ConsoleIo($this->_out, $this->_err, $this->_in);

        try {
            $this->_exitCode = $runner->run($args, $io);
        } catch (MissingConsoleInputException $e) {
            $messages = $this->_out->messages();
            if (count($messages)) {
                $e->setQuestion($messages[count($messages) - 1]);
            }
            throw $e;
        } catch (StopException $exception) {
            $this->_exitCode = $exception->getCode();
        }
    }

    /**
     * Cleans state to get ready for the next test
     *
     * @after
     * @return void
     * @psalm-suppress PossiblyNullPropertyAssignmentValue
     */
    public function cleanupConsoleTrait(): void
    {
        $this->_exitCode = null;
        $this->_out = null;
        $this->_err = null;
        $this->_in = null;
        $this->_useCommandRunner = false;
        $this->_appClass = null;
        $this->_appArgs = null;
    }

    /**
     * Set this test case to use the CommandRunner rather than the legacy
     * ShellDispatcher
     *
     * @return void
     */
    public function useCommandRunner(): void
    {
        $this->_useCommandRunner = true;
    }

    /**
     * Configure the application class to use in console integration tests.
     *
     * @param string $class The application class name.
     * @param array|null $constructorArgs The constructor arguments for your application class.
     * @return void
     * @psalm-param class-string<\Cake\Core\ConsoleApplicationInterface> $class
     */
    public function configApplication(string $class, ?array $constructorArgs): void
    {
        $this->_appClass = $class;
        $this->_appArgs = $constructorArgs;
    }

    /**
     * Asserts shell exited with the expected code
     *
     * @param int $expected Expected exit code
     * @param string $message Failure message
     * @return void
     */
    public function assertExitCode(int $expected, string $message = ''): void
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
    public function assertOutputEmpty(string $message = ''): void
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
    public function assertOutputContains(string $expected, string $message = ''): void
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
    public function assertOutputNotContains(string $expected, string $message = ''): void
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
    public function assertOutputRegExp(string $pattern, string $message = ''): void
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
    protected function assertOutputContainsRow(array $row, string $message = ''): void
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
    public function assertErrorContains(string $expected, string $message = ''): void
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
    public function assertErrorRegExp(string $pattern, string $message = ''): void
    {
        $this->assertThat($pattern, new ContentsRegExp($this->_err->messages(), 'error output'), $message);
    }

    /**
     * Asserts that `stderr` is empty
     *
     * @param string $message The message to output when the assertion fails.
     * @return void
     */
    public function assertErrorEmpty(string $message = ''): void
    {
        $this->assertThat(null, new ContentsEmpty($this->_err->messages(), 'error output'), $message);
    }

    /**
     * Builds the appropriate command dispatcher
     *
     * @return \Cake\Console\CommandRunner|\Cake\TestSuite\LegacyCommandRunner
     */
    protected function makeRunner()
    {
        if ($this->_useCommandRunner) {
            if ($this->_appClass) {
                $appClass = $this->_appClass;
            } else {
                /** @psalm-var class-string<\Cake\Core\ConsoleApplicationInterface> */
                $appClass = Configure::read('App.namespace') . '\Application';
            }
            $appArgs = $this->_appArgs ?: [CONFIG];

            return new CommandRunner(new $appClass(...$appArgs));
        }

        return new LegacyCommandRunner();
    }

    /**
     * Creates an $argv array from a command string
     *
     * @param string $command Command string
     * @return string[]
     */
    protected function commandStringToArgs(string $command): array
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
