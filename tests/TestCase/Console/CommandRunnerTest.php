<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Console;

use Cake\Console\CommandCollection;
use Cake\Console\CommandRunner;
use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Http\BaseApplication;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\Stub\ConsoleOutput;
use TestApp\Shell\SampleShell;

/**
 * Test case for the CommandCollection
 */
class CommandRunnerTest extends TestCase
{
    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        $this->config = dirname(dirname(__DIR__));
    }

    /**
     * Test that the console hook not returning a command collection
     * raises an error.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The application's `console` method did not return a CommandCollection.
     * @return void
     */
    public function testRunConsoleHookFailure()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['console', 'middleware', 'bootstrap'])
            ->setConstructorArgs([$this->config])
            ->getMock();
        $runner = new CommandRunner($app);
        $runner->run(['cake', '-h']);
    }

    /**
     * Test that running with empty argv fails
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unknown root command. Was expecting `cake`
     * @return void
     */
    public function testRunMissingRootCommand()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $runner = new CommandRunner($app);
        $runner->run([]);
    }

    /**
     * Test that running an unknown command raises an error.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unknown root command `bad`. Was expecting `cake`
     * @return void
     */
    public function testRunInvalidRootCommand()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $runner = new CommandRunner($app);
        $runner->run(['bad', 'i18n']);
    }

    /**
     * Test that running an unknown command raises an error.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unknown command `cake nope`. Run `cake --help` to get the list of valid commands.
     * @return void
     */
    public function testRunInvalidCommand()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $runner = new CommandRunner($app);
        $runner->run(['cake', 'nope', 'nope', 'nope']);
    }

    /**
     * Test using `cake --help` invokes the help command
     *
     * @return void
     */
    public function testRunHelpLongOption()
    {
        $this->markTestIncomplete();
    }

    /**
     * Test using `cake -h` invokes the help command
     *
     * @return void
     */
    public function testRunHelpShortOption()
    {
        $this->markTestIncomplete();
    }

    /**
     * Test using `cake --verson` invokes the version command
     *
     * @return void
     */
    public function testRunVersionLongOption()
    {
        $this->markTestIncomplete();
    }

    /**
     * Test using `cake -v` invokes the version command
     *
     * @return void
     */
    public function testRunVersionShortOption()
    {
        $this->markTestIncomplete();
    }

    /**
     * Test running a valid command
     *
     * @return void
     */
    public function testRunValidCommand()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'routes'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $contents = implode("\n", $output->messages());
        $this->assertContains('URI template', $contents);
        $this->assertContains('Welcome to CakePHP', $contents);
    }

    /**
     * Test running a valid raising an error
     *
     * @return void
     */
    public function testRunValidCommandWithAbort()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap', 'console'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $commands = new CommandCollection(['failure' => SampleShell::class]);
        $app->method('console')->will($this->returnValue($commands));

        $output = new ConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'failure', 'with_abort'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_ERROR, $result);
    }

    /**
     * Test returning a non-zero value
     *
     * @return void
     */
    public function testRunValidCommandReturnInteger()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap', 'console'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $commands = new CommandCollection(['failure' => SampleShell::class]);
        $app->method('console')->will($this->returnValue($commands));

        $output = new ConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'failure', 'returnValue'], $this->getMockIo($output));
        $this->assertSame(99, $result);
    }

    protected function getMockIo($output)
    {
        $io = $this->getMockBuilder(ConsoleIo::class)
            ->setConstructorArgs([$output, $output, null, null])
            ->setMethods(['in'])
            ->getMock();

        return $io;
    }
}
