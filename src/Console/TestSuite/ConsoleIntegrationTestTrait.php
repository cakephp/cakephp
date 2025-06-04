<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.7.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\TestSuite;

use Cake\Console\CommandInterface;
use Cake\Console\CommandRunner;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Exception\StopException;
use Cake\Console\TestSuite\Constraint\ContentsContain;
use Cake\Console\TestSuite\Constraint\ContentsContainRow;
use Cake\Console\TestSuite\Constraint\ContentsEmpty;
use Cake\Console\TestSuite\Constraint\ContentsNotContain;
use Cake\Console\TestSuite\Constraint\ContentsRegExp;
use Cake\Console\TestSuite\Constraint\ExitCode;
use Cake\Core\ConsoleApplicationInterface;
use Cake\Core\TestSuite\ContainerStubTrait;
use Cake\Error\Debugger;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\After;

/**
 * A bundle of methods that makes testing commands
 * and shell classes easier.
 *
 * Enables you to call commands/shells with a
 * full application context.
 */
trait ConsoleIntegrationTestTrait
{
    use ContainerStubTrait;

    /**
     * Last exit code
     *
     * @var int|null
     */
    protected ?int $_exitCode = null;

    /**
     * Console output stub
     *
     * @var \Cake\Console\TestSuite\StubConsoleOutput|null
     */
    protected ?StubConsoleOutput $_out = null;

    /**
     * Console error output stub
     *
     * @var \Cake\Console\TestSuite\StubConsoleOutput|null
     */
    protected ?StubConsoleOutput $_err = null;

    /**
     * Console input mock
     *
     * @var \Cake\Console\TestSuite\StubConsoleInput|null
     */
    protected ?StubConsoleInput $_in = null;

    /**
     * Runs CLI integration test
     *
     * @param string $command Command to run
     * @param array $input Input values to pass to an interactive shell
     * @throws \Cake\Console\TestSuite\MissingConsoleInputException
     * @throws \InvalidArgumentException
     * @return void
     */
    public function exec(string $command, array $input = []): void
    {
        $runner = $this->makeRunner();

        $this->_out ??= new StubConsoleOutput();
        $this->_err ??= new StubConsoleOutput();
        if ($this->_in === null) {
            $this->_in = new StubConsoleInput($input);
        } elseif ($input) {
            throw new InvalidArgumentException(
                'You can use `$input` only if `$_in` property is null and will be reset.',
            );
        }
        $this->_out->clear();
        $this->_err->clear();

        $args = $this->commandStringToArgs("cake {$command}");
        $io = new ConsoleIo($this->_out, $this->_err, $this->_in);

        try {
            $this->_exitCode = $runner->run($args, $io);
        } catch (MissingConsoleInputException $e) {
            $messages = $this->_out->messages();
            if ($messages !== []) {
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
     * @return void
     */
    #[After]
    public function cleanupConsoleTrait(): void
    {
        $this->_exitCode = null;
        $this->_out = null;
        $this->_err = null;
        $this->_in = null;
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
        $this->assertThat(
            $expected,
            new ExitCode($this->_exitCode, $this->_out->messages(), $this->_err->messages()),
            $message,
        );
    }

    /**
     * Asserts shell exited with the CommandInterface::CODE_SUCCESS
     *
     * @param string $message Failure message
     * @return void
     */
    public function assertExitSuccess(string $message = ''): void
    {
        $this->assertThat(
            CommandInterface::CODE_SUCCESS,
            new ExitCode($this->_exitCode, $this->_out->messages(), $this->_err->messages()),
            $message,
        );
    }

    /**
     * Asserts shell exited with CommandInterface::CODE_ERROR
     *
     * @param string $message Failure message
     * @return void
     */
    public function assertExitError(string $message = ''): void
    {
        $this->assertThat(
            CommandInterface::CODE_ERROR,
            new ExitCode($this->_exitCode, $this->_out->messages(), $this->_err->messages()),
            $message,
        );
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
     * Dump the exit code, stdout and stderr from the most recently run command
     *
     * @param resource|null $stream The stream to write to. Defaults to STDOUT
     * @return void
     */
    public function debugOutput($stream = null): void
    {
        $output = new ConsoleOutput($stream ?? 'php://stdout');
        if (class_exists(Debugger::class)) {
            $trace = Debugger::trace(['start' => 0, 'depth' => 1, 'format' => 'array']);
            $file = $trace[0]['file'];
            $line = $trace[0]['line'];
            $output->write("{$file} on {$line}");
        }
        $output->write('########## debugOutput() ##########');

        if ($this->_exitCode !== null) {
            $output->write('<info>Exit Code</info>');
            $output->write((string)$this->_exitCode, 2);
        }
        $output->write('<info>STDOUT</info>');
        $output->write($this->_out->messages(), 2);

        $output->write('<info>STDERR</info>');
        $output->write($this->_err->messages());
        $output->write('###################################');
    }

    /**
     * Builds the appropriate command dispatcher
     *
     * @return \Cake\Console\CommandRunner
     */
    protected function makeRunner(): CommandRunner
    {
        $app = $this->createApp();
        assert($app instanceof ConsoleApplicationInterface);

        return new CommandRunner($app);
    }

    /**
     * Creates an $argv array from a command string
     *
     * @param string $command Command string
     * @return array<string>
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
                if ($arg !== '') {
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

// phpcs:disable
class_alias(
    'Cake\Console\TestSuite\ConsoleIntegrationTestTrait',
    'Cake\TestSuite\ConsoleIntegrationTestTrait'
);
// phpcs:enable
