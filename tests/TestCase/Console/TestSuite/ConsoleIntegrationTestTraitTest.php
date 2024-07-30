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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\TestSuite;

use Cake\Console\CommandInterface;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Console\TestSuite\MissingConsoleInputException;
use Cake\TestSuite\TestCase;
use Iterator;
use PHPUnit\Framework\AssertionFailedError;
use stdClass;

class ConsoleIntegrationTestTraitTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setAppNamespace();
    }

    /**
     * tests exec when using the command runner
     */
    public function testExecWithCommandRunner(): void
    {
        $this->exec('');

        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('Current Paths');
        $this->assertExitSuccess();
    }

    /**
     * tests exec
     */
    public function testExec(): void
    {
        $this->exec('sample');

        $this->assertOutputContains('SampleCommand');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * tests that exec catches a StopException
     */
    public function testExecCommandWithStopException(): void
    {
        $this->exec('abort_command');
        $this->assertExitCode(127);
        $this->assertErrorContains('Command aborted');
    }

    /**
     * tests that exec with a format specifier
     */
    public function testExecCommandWithFormatSpecifier(): void
    {
        $this->exec('format_specifier_command');
        $this->assertOutputContains('format specifier');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * tests a valid core command
     */
    public function testExecCoreCommand(): void
    {
        $this->exec('routes');

        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * tests exec with an arg and an option
     */
    public function testExecWithArgsAndOption(): void
    {
        $this->exec('integration arg --opt="some string"');

        $this->assertErrorEmpty();
        $this->assertOutputContains('arg: arg');
        $this->assertOutputContains('opt: some string');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * tests exec with an arg and an option
     */
    public function testExecWithJsonArg(): void
    {
        $this->exec("integration '{\"key\":\"value\"}'");

        $this->assertErrorEmpty();
        $this->assertOutputContains('arg: {"key":"value"}');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    /**
     * tests exec with missing required argument
     */
    public function testExecWithMissingRequiredArg(): void
    {
        $this->exec('integration');

        $this->assertErrorContains('Missing required argument');
        $this->assertErrorContains('`arg` argument is required');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
    }

    /**
     * tests exec with input
     */
    public function testExecWithInput(): void
    {
        $this->exec('bridge', ['javascript']);

        $this->assertErrorContains('No!');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
    }

    /**
     * tests exec with fewer inputs than questions
     */
    public function testExecWithMissingInput(): void
    {
        $this->expectException(MissingConsoleInputException::class);
        $this->expectExceptionMessage('no more input');
        $this->exec('bridge', ['cake']);
    }

    /**
     * tests exec with multiple inputs
     */
    public function testExecWithMultipleInput(): void
    {
        $this->exec('bridge', ['cake', 'blue']);

        $this->assertOutputContains('You may pass');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
    }

    public function testExecWithMockServiceDependencies(): void
    {
        $this->mockService(stdClass::class, fn(): mixed => json_decode('{"console-mock":true}'));
        $this->exec('dependency');

        $this->assertOutputContains('constructor inject: {"console-mock":true}');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
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
        $result = $this->commandStringToArgs(sprintf("   --json='%s'", $json));
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
     * @dataProvider assertionFailureMessagesProvider
     */
    public function testAssertionFailureMessages(string $assertion, string $message, string $command, mixed ...$rest): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('#' . $message . '.?#');

        $this->exec($command);

        call_user_func_array($this->$assertion(...), $rest);
    }

    /**
     * data provider for assertion failure messages
     *
     * @return array
     */
    public static function assertionFailureMessagesProvider(): Iterator
    {
        yield 'assertExitCode' => ['assertExitCode', 'Failed asserting that `1` matches exit code `0`', 'routes', CommandInterface::CODE_ERROR];
        yield 'assertOutputEmpty' => ['assertOutputEmpty', 'Failed asserting that output is empty', 'routes'];
        yield 'assertOutputContains' => ['assertOutputContains', "Failed asserting that 'missing' is in output", 'routes', 'missing'];
        yield 'assertOutputNotContains' => ['assertOutputNotContains', "Failed asserting that 'controller' is not in output", 'routes', 'controller'];
        yield 'assertOutputRegExp' => ['assertOutputRegExp', 'Failed asserting that `/missing/` PCRE pattern found in output', 'routes', '/missing/'];
        yield 'assertOutputContainsRow' => ['assertOutputContainsRow', 'Failed asserting that `.*` row was in output', 'routes', ['test', 'missing']];
        yield 'assertErrorContains' => ['assertErrorContains', "Failed asserting that 'test' is in error output", 'routes', 'test'];
        yield 'assertErrorRegExp' => ['assertErrorRegExp', 'Failed asserting that `/test/` PCRE pattern found in error output', 'routes', '/test/'];
    }
}
