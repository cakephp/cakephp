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
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Console\CommandRunner;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\TestSuite\Stub\ConsoleOutput;

/**
 * A test case class intended to make integration tests of cake console commands
 * easier.
 */
class ConsoleIntegrationTestCase extends TestCase
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
     * @var int
     */
    protected $_exitCode;

    /**
     * Console output stub
     *
     * @var ConsoleOutput
     */
    protected $_out;

    /**
     * Console error output stub
     *
     * @var ConsoleOutput
     */
    protected $_err;

    /**
     * Console input mock
     *
     * @var ConsoleInput
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
        $runner = $this->_makeRunner();

        $this->_out = new ConsoleOutput();
        $this->_err = new ConsoleOutput();
        $this->_in = $this->getMockBuilder(ConsoleInput::class)
            ->disableOriginalConstructor()
            ->setMethods(['read'])
            ->getMock();

        $i = 0;
        foreach ($input as $in) {
            $this->_in
                ->expects($this->at($i++))
                ->method('read')
                ->will($this->returnValue($in));
        }

        $args = $this->_commandStringToArgs("cake $command");

        $io = new ConsoleIo($this->_out, $this->_err, $this->_in);

        $this->_exitCode = $runner->run($args, $io);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

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
    public function enableCommandRunner()
    {
        $this->_useCommandRunner = true;
    }

    /**
     * Asserts shell exited with the expected code
     *
     * @param int $expected Expected exit code
     * @param string $message Failure message to be appended to the generated message
     * @return void
     */
    public function assertExitCode($expected, $message = '')
    {
        $message = sprintf(
            'Shell exited with code %d instead of the expected code %d. %s',
            $this->_exitCode,
            $expected,
            $message
        );
        $this->assertSame($expected, $this->_exitCode, $message);
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
        $output = implode(PHP_EOL, $this->_out->messages());
        $this->assertContains($expected, $output, $message);
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
        $output = implode(PHP_EOL, $this->_err->messages());
        $this->assertContains($expected, $output, $message);
    }

    /**
     * Builds the appropriate command dispatcher
     *
     * @return CommandRunner|LegacyCommandRunner
     */
    protected function _makeRunner()
    {
        if ($this->_useCommandRunner) {
            $applicationClassName = Configure::read('App.namespace') . '\Application';

            return new CommandRunner(new $applicationClassName([CONFIG]));
        }

        return new LegacyCommandRunner();
    }

    /**
     * Dispatches the command string to a script that returns the argv array
     * parsed by PHP
     *
     * @param string $command Command string
     * @return array
     */
    protected function _commandStringToArgs($command)
    {
        $argvScript = CORE_TESTS . 'argv.php';
        $jsonArgv = shell_exec("$argvScript $command");

        $argv = json_decode($jsonArgv);
        array_shift($argv);

        return $argv;
    }
}
