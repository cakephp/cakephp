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
     * tests exec when using the command runner
     *
     * @return void
     */
    public function testExecWithCommandRunner()
    {
        $this->useCommandRunner();

        $this->exec('routes');

        $this->assertExitCode(Shell::CODE_SUCCESS);
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

        $this->assertExitCode(Shell::CODE_SUCCESS);
    }

    /**
     * tests exec with an arg and an option
     *
     * @return void
     */
    public function testExecWithArgsAndOption()
    {
        $this->exec('integration args_and_options arg --opt="some string"');

        $this->assertErrorEmpty();
        $this->assertOutputContains('arg: arg');
        $this->assertOutputContains('opt: some string');
        $this->assertExitCode(Shell::CODE_SUCCESS);
    }

    /**
     * tests exec with missing required argument
     *
     * @return void
     */
    public function testExecWithMissingRequiredArg()
    {
        $this->exec('integration args_and_options');

        $this->assertOutputEmpty();
        $this->assertErrorContains('Missing required arguments');
        $this->assertErrorContains('arg is required');
        $this->assertExitCode(Shell::CODE_ERROR);
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
     * tests assertOutputRegExp assertion
     *
     * @return void
     */
    public function testAssertOutputRegExp()
    {
        $this->exec('routes');

        $this->assertOutputRegExp('/^\+[\-\+]+\+$/m');
    }

    /**
     * tests assertErrorRegExp assertion
     *
     * @return void
     */
    public function testAssertErrorRegExp()
    {
        $this->exec('integration args_and_options');

        $this->assertErrorRegExp('/\<error\>(.+)\<\/error\>/');
    }

    /**
     * tests _commandStringToArgs
     *
     * @return void
     */
    public function testCommandStringToArgs()
    {
        $result = $this->_commandStringToArgs('command --something=nothing --with-spaces="quote me on that" \'quoted \"arg\"\'');
        $expected = [
            'command',
            '--something=nothing',
            '--with-spaces=quote me on that',
            'quoted \"arg\"',
        ];
        $this->assertSame($expected, $result);

        $json = json_encode(['key' => '"val"', 'this' => true]);
        $result = $this->_commandStringToArgs("   --json='$json'");
        $expected = [
            '--json=' . $json
        ];
        $this->assertSame($expected, $result);
    }
}
