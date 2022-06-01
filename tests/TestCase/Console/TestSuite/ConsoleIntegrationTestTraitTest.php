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
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\TestSuite;

use Cake\Command\Command;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Console\TestSuite\MissingConsoleInputException;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\AssertionFailedError;
use stdClass;

class ConsoleIntegrationTestTraitTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->setAppNamespace();
    }

    /**
     * tests exec when using the command runner
     */
    public function testExecWithCommandRunner(): void
    {
        $this->useCommandRunner();
        $this->exec('');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Current Paths');
        $this->assertExitSuccess();
    }

    /**
     * tests exec
     */
    public function testExec(): void
    {
        $this->exec('sample');

        $this->assertOutputContains('SampleShell');
        $this->assertExitCode(Command::CODE_SUCCESS);
    }

    /**
     * tests that exec catches a StopException
     */
    public function testExecShellWithStopException(): void
    {
        $this->exec('integration abort_shell');
        $this->assertExitCode(Command::CODE_ERROR);
        $this->assertExitError();
        $this->assertErrorContains('Shell aborted');
    }

    /**
     * tests that exec catches a StopException
     */
    public function testExecCommandWithStopException(): void
    {
        $this->useCommandRunner();
        $this->exec('abort_command');
        $this->assertExitCode(127);
        $this->assertErrorContains('Command aborted');
    }

    /**
     * tests that exec with a format specifier
     */
    public function testExecCommandWithFormatSpecifier(): void
    {
        $this->useCommandRunner();
        $this->exec('format_specifier_command');
        $this->assertOutputContains('format specifier');
        $this->assertExitCode(Command::CODE_SUCCESS);
    }

    /**
     * tests a valid core command
     */
    public function testExecCoreCommand(): void
    {
        $this->useCommandRunner();
        $this->exec('routes');

        $this->assertExitCode(Command::CODE_SUCCESS);
    }

    /**
     * tests exec with an arg and an option
     */
    public function testExecWithArgsAndOption(): void
    {
        $this->exec('integration args_and_options arg --opt="some string"');

        $this->assertErrorEmpty();
        $this->assertOutputContains('arg: arg');
        $this->assertOutputContains('opt: some string');
        $this->assertExitCode(Command::CODE_SUCCESS);
    }

    /**
     * tests exec with an arg and an option
     */
    public function testExecWithJsonArg(): void
    {
        $this->exec("integration args_and_options '{\"key\":\"value\"}'");

        $this->assertErrorEmpty();
        $this->assertOutputContains('arg: {"key":"value"}');
        $this->assertExitCode(Command::CODE_SUCCESS);
    }

    /**
     * tests exec with missing required argument
     */
    public function testExecWithMissingRequiredArg(): void
    {
        $this->exec('integration args_and_options');

        $this->assertOutputEmpty();
        $this->assertErrorContains('Missing required argument');
        $this->assertErrorContains('`arg` argument is required');
        $this->assertExitCode(Command::CODE_ERROR);
    }

    /**
     * tests exec with input
     */
    public function testExecWithInput(): void
    {
        $this->exec('integration bridge', ['javascript']);

        $this->assertErrorContains('No!');
        $this->assertExitCode(Command::CODE_ERROR);
    }

    /**
     * tests exec with fewer inputs than questions
     */
    public function testExecWithMissingInput(): void
    {
        $this->expectException(MissingConsoleInputException::class);
        $this->expectExceptionMessage('no more input');
        $this->exec('integration bridge', ['cake']);
    }

    /**
     * tests exec with multiple inputs
     */
    public function testExecWithMultipleInput(): void
    {
        $this->exec('integration bridge', ['cake', 'blue']);

        $this->assertOutputContains('You may pass');
        $this->assertExitCode(Command::CODE_SUCCESS);
    }

    public function testExecWithMockServiceDependencies(): void
    {
        $this->mockService(stdClass::class, function () {
            return json_decode('{"console-mock":true}');
        });
        $this->useCommandRunner();
        $this->exec('dependency');

        $this->assertOutputContains('constructor inject: {"console-mock":true}');
        $this->assertExitCode(Command::CODE_SUCCESS);
    }

    /**
     * tests assertOutputRegExp assertion
     */
    public function testAssertOutputRegExp(): void
    {
        $this->exec('sample');

        $this->assertOutputRegExp('/^[A-Z]+/mi');
    }

    /**
     * tests assertErrorRegExp assertion
     */
    public function testAssertErrorRegExp(): void
    {
        $this->exec('integration args_and_options');

        $this->assertErrorRegExp('/\<error\>(.+)\<\/error\>/');
    }

    /**
     * tests commandStringToArgs
     */
    public function testCommandStringToArgs(): void
    {
        $result = $this->commandStringToArgs('command --something=nothing --with-spaces="quote me on that" \'quoted \"arg\"\'');
        $expected = [
            'command',
            '--something=nothing',
            '--with-spaces=quote me on that',
            'quoted \"arg\"',
        ];
        $this->assertSame($expected, $result);

        $json = json_encode(['key' => '"val"', 'this' => true]);
        $result = $this->commandStringToArgs("   --json='$json'");
        $expected = [
            '--json=' . $json,
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * tests failure messages for assertions
     *
     * @param string $assertion Assertion method
     * @param string $message Expected failure message
     * @param string $command Command to test
     * @param mixed ...$rest
     * @dataProvider assertionFailureMessagesProvider
     */
    public function testAssertionFailureMessages($assertion, $message, $command, ...$rest): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('#' . preg_quote($message, '#') . '.?#');

        $this->useCommandRunner();
        $this->exec($command);

        call_user_func_array([$this, $assertion], $rest);
    }

    /**
     * data provider for assertion failure messages
     *
     * @return array
     */
    public function assertionFailureMessagesProvider(): array
    {
        return [
            'assertExitCode' => ['assertExitCode', 'Failed asserting that 1 matches exit code 0', 'routes', Command::CODE_ERROR],
            'assertOutputEmpty' => ['assertOutputEmpty', 'Failed asserting that output is empty', 'routes'],
            'assertOutputContains' => ['assertOutputContains', 'Failed asserting that \'missing\' is in output', 'routes', 'missing'],
            'assertOutputNotContains' => ['assertOutputNotContains', 'Failed asserting that \'controller\' is not in output', 'routes', 'controller'],
            'assertOutputRegExp' => ['assertOutputRegExp', 'Failed asserting that `/missing/` PCRE pattern found in output', 'routes', '/missing/'],
            'assertOutputContainsRow' => ['assertOutputContainsRow', 'Failed asserting that `Array (...)` row was in output', 'routes', ['test', 'missing']],
            'assertErrorContains' => ['assertErrorContains', 'Failed asserting that \'test\' is in error output', 'routes', 'test'],
            'assertErrorRegExp' => ['assertErrorRegExp', 'Failed asserting that `/test/` PCRE pattern found in error output', 'routes', '/test/'],
            'assertErrorEmpty' => ['assertErrorEmpty', 'Failed asserting that error output is empty', 'integration args_and_options'],
        ];
    }
}
