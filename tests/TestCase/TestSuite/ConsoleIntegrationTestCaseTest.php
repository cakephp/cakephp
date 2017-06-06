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
     * tests cli
     *
     * @return void
     */
    public function testCli()
    {
        $this->cli('');

        $this->assertOutputContains('Welcome to CakePHP');
        $this->assertExitCode(Shell::CODE_ERROR);
    }

    /**
     * tests a valid core command
     *
     * @return void
     */
    public function testCliCoreCommand()
    {
        $this->cli('routes');

        $this->assertOutputContains('Welcome to CakePHP');
        $this->assertExitCode(Shell::CODE_SUCCESS);
    }

    /**
     * tests cli with input
     *
     * @return void
     */
    public function testCliWithInput()
    {
        $this->cli('sample bridge', ['javascript']);

        $this->assertErrorContains('No!');
        $this->assertExitCode(Shell::CODE_ERROR);
    }

    /**
     * tests cli with multiple inputs
     *
     * @return void
     */
    public function testCliWithMultipleInput()
    {
        $this->cli('sample bridge', ['cake', 'blue']);

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
