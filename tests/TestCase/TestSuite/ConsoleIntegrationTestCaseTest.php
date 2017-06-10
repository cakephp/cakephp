<?php
namespace Cake\Test\TestCase\TestSuite;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\TestSuite\ConsoleIntegrationTestCase;

class ConsoleIntegrationTestCaseTest extends ConsoleIntegrationTestCase
{

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Configure::write('App.namespace', 'TestApp');
    }

    /**
     * tests exec
     *
     * @return void
     */
    public function testExec()
    {
        $this->exec('');

        $this->assertOutputContains('Welcome to CakePHP');
        $this->assertExitCode(Shell::CODE_ERROR);
    }

    /**
     * tests a valid core command
     *
     * @return void
     */
    public function testExecCoreCommand()
    {
        $this->exec('routes');

        $this->assertOutputContains('Welcome to CakePHP');
        $this->assertExitCode(Shell::CODE_SUCCESS);
    }

    /**
     * tests exec with input
     *
     * @return void
     */
    public function testExecWithInput()
    {
        $this->exec('integration bridge', ['javascript']);

        $this->assertErrorContains('No!');
        $this->assertExitCode(Shell::CODE_ERROR);
    }

    /**
     * tests exec with multiple inputs
     *
     * @return void
     */
    public function testExecWithMultipleInput()
    {
        $this->exec('integration bridge', ['cake', 'blue']);

        $this->assertOutputContains('You may pass');
        $this->assertExitCode(Shell::CODE_SUCCESS);
    }

    /**
     * tests _commandStringToArgs
     *
     * @return void
     */
    public function testCommandStringToArgs()
    {
        $result = $this->_commandStringToArgs('command --something=nothing --with-spaces="quote me on that"');
        $expected = [
            'command',
            '--something=nothing',
            '--with-spaces=quote me on that'
        ];
        $this->assertSame($expected, $result);
    }
}
