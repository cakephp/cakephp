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
use Cake\TestSuite\Stub\ConsoleOutput;
use Cake\TestSuite\TestCase;
use TestApp\Shell\SampleShell;

/**
 * Test case for the CommandCollection
 */
class CommandRunnerTest extends TestCase
{
    /**
     * Tracking property for event triggering
     *
     * @var bool
     */
    protected $eventTriggered = false;

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
     * @return void
     */
    public function testRunConsoleHookFailure()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The application\'s `console` method did not return a CommandCollection.');
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
     * @return void
     */
    public function testRunMissingRootCommand()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot run any commands. No arguments received.');
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
     * @return void
     */
    public function testRunInvalidCommand()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown command `cake nope`. Run `cake --help` to get the list of valid commands.');
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
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', '--help'], $this->getMockIo($output));
        $this->assertSame(0, $result);
        $messages = implode("\n", $output->messages());
        $this->assertContains('Current Paths', $messages);
        $this->assertContains('- i18n', $messages);
        $this->assertContains('Available Commands', $messages);
    }

    /**
     * Test using `cake -h` invokes the help command
     *
     * @return void
     */
    public function testRunHelpShortOption()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', '-h'], $this->getMockIo($output));
        $this->assertSame(0, $result);
        $messages = implode("\n", $output->messages());
        $this->assertContains('- i18n', $messages);
        $this->assertContains('Available Commands', $messages);
    }

    /**
     * Test that no command outputs the command list
     *
     * @return void
     */
    public function testRunNoCommand()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app);
        $result = $runner->run(['cake'], $this->getMockIo($output));

        $this->assertSame(0, $result, 'help output is success.');
        $messages = implode("\n", $output->messages());
        $this->assertContains('No command provided. Choose one of the available commands', $messages);
        $this->assertContains('- i18n', $messages);
        $this->assertContains('Available Commands', $messages);
    }

    /**
     * Test using `cake --verson` invokes the version command
     *
     * @return void
     */
    public function testRunVersionAlias()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', '--version'], $this->getMockIo($output));
        $this->assertContains(Configure::version(), $output->messages()[0]);
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
    }

    /**
     * Test running a valid command and that backwards compatible
     * inflection is hooked up.
     *
     * @return void
     */
    public function testRunValidCommandInflection()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'OrmCache', 'build'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $contents = implode("\n", $output->messages());
        $this->assertContains('Cache', $contents);
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

    /**
     * Ensure that the root command name propagates to shell help
     *
     * @return void
     */
    public function testRunRootNamePropagates()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap', 'console'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $commands = new CommandCollection(['sample' => SampleShell::class]);
        $app->method('console')->will($this->returnValue($commands));

        $output = new ConsoleOutput();

        $runner = new CommandRunner($app, 'widget');
        $runner->run(['widget', 'sample', '-h'], $this->getMockIo($output));
        $result = implode("\n", $output->messages());
        $this->assertContains('widget sample [-h]', $result);
        $this->assertNotContains('cake sample [-h]', $result);
    }

    /**
     * Test that run() fires off the buildCommands event.
     *
     * @return void
     */
    public function testRunTriggersBuildCommandsEvent()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setMethods(['middleware', 'bootstrap'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $runner->getEventManager()->on('Console.buildCommands', function ($event, $commands) {
            $this->assertInstanceOf(CommandCollection::class, $commands);
            $this->eventTriggered = true;
        });
        $result = $runner->run(['cake', '--version'], $this->getMockIo($output));
        $this->assertTrue($this->eventTriggered, 'Should have triggered event.');
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
