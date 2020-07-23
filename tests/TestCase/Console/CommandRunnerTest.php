<?php
declare(strict_types=1);

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
namespace Cake\Test\TestCase\Console;

use Cake\Console\CommandCollection;
use Cake\Console\CommandFactoryInterface;
use Cake\Console\CommandRunner;
use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Core\ConsoleApplicationInterface;
use Cake\Event\EventManager;
use Cake\Http\BaseApplication;
use Cake\Routing\Router;
use Cake\TestSuite\Stub\ConsoleOutput;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestApp\Command\AbortCommand;
use TestApp\Command\DemoCommand;
use TestApp\Shell\SampleShell;

/**
 * Test case for the CommandCollection
 */
class CommandRunnerTest extends TestCase
{
    /**
     * @var string
     */
    protected $config;

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
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        $this->config = dirname(dirname(__DIR__));
    }

    /**
     * test event manager proxies to the application.
     *
     * @return void
     */
    public function testEventManagerProxies()
    {
        $app = $this->getMockForAbstractClass(
            BaseApplication::class,
            [$this->config]
        );

        $runner = new CommandRunner($app);
        $this->assertSame($app->getEventManager(), $runner->getEventManager());
    }

    /**
     * test event manager cannot be set on applications without events.
     *
     * @return void
     */
    public function testGetEventManagerNonEventedApplication()
    {
        $app = $this->createMock(ConsoleApplicationInterface::class);

        $runner = new CommandRunner($app);
        $this->assertSame(EventManager::instance(), $runner->getEventManager());
    }

    /**
     * test event manager cannot be set on applications without events.
     *
     * @return void
     */
    public function testSetEventManagerNonEventedApplication()
    {
        $this->expectException(InvalidArgumentException::class);
        $app = $this->createMock(ConsoleApplicationInterface::class);

        $events = new EventManager();
        $runner = new CommandRunner($app);
        $runner->setEventManager($events);
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
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
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
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app);
        $runner->run(['cake', 'nope', 'nope', 'nope'], $this->getMockIo($output));

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString(
            'Unknown command `cake nope`. Run `cake --help` to get the list of commands.',
            $messages
        );
    }

    /**
     * Test that running an unknown command gives suggestions.
     *
     * @return void
     */
    public function testRunInvalidCommandSuggestion()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app);
        $runner->run(['cake', 'cache'], $this->getMockIo($output));

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString(
            "Did you mean: `cache clear`?\n" .
            "\n" .
            "Other valid choices:\n" .
            "\n" .
            '- help',
            $messages
        );
    }

    /**
     * Test using `cake --help` invokes the help command
     *
     * @return void
     */
    public function testRunHelpLongOption()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', '--help'], $this->getMockIo($output));
        $this->assertSame(0, $result);
        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('Current Paths', $messages);
        $this->assertStringContainsString('- i18n', $messages);
        $this->assertStringContainsString('Available Commands', $messages);
    }

    /**
     * Test using `cake -h` invokes the help command
     *
     * @return void
     */
    public function testRunHelpShortOption()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', '-h'], $this->getMockIo($output));
        $this->assertSame(0, $result);
        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('- i18n', $messages);
        $this->assertStringContainsString('Available Commands', $messages);
    }

    /**
     * Test that no command outputs the command list
     *
     * @return void
     */
    public function testRunNoCommand()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app);
        $result = $runner->run(['cake'], $this->getMockIo($output));

        $this->assertSame(0, $result, 'help output is success.');
        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('No command provided. Choose one of the available commands', $messages);
        $this->assertStringContainsString('- i18n', $messages);
        $this->assertStringContainsString('Available Commands', $messages);
    }

    /**
     * Test using `cake --version` invokes the version command
     *
     * @return void
     */
    public function testRunVersionAlias()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', '--version'], $this->getMockIo($output));
        $this->assertStringContainsString(Configure::version(), $output->messages()[0]);
    }

    /**
     * Test running a valid command
     *
     * @return void
     */
    public function testRunValidCommand()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'routes'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $contents = implode("\n", $output->messages());
        $this->assertStringContainsString('URI template', $contents);
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
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new ConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'schema_cache', 'build'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $contents = implode("\n", $output->messages());
        $this->assertStringContainsString('Cache', $contents);
    }

    /**
     * Test running a valid raising an error
     *
     * @return void
     */
    public function testRunValidCommandWithAbort()
    {
        $app = $this->makeAppWithCommands(['failure' => SampleShell::class]);
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
        $app = $this->makeAppWithCommands(['failure' => SampleShell::class]);
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
        $app = $this->makeAppWithCommands(['sample' => SampleShell::class]);
        $output = new ConsoleOutput();

        $runner = new CommandRunner($app, 'widget');
        $runner->run(['widget', 'sample', '-h'], $this->getMockIo($output));
        $result = implode("\n", $output->messages());
        $this->assertStringContainsString('widget sample [-h]', $result);
        $this->assertStringNotContainsString('cake sample [-h]', $result);
    }

    /**
     * Test running a valid command
     *
     * @return void
     */
    public function testRunValidCommandClass()
    {
        $app = $this->makeAppWithCommands(['ex' => DemoCommand::class]);
        $output = new ConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'ex'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('Demo Command!', $messages);
    }

    /**
     * Test running a valid command with spaces in the name
     *
     * @return void
     */
    public function testRunValidCommandSubcommandName()
    {
        $app = $this->makeAppWithCommands([
            'tool build' => DemoCommand::class,
            'tool' => AbortCommand::class,
        ]);
        $output = new ConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'tool', 'build'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('Demo Command!', $messages);
    }

    /**
     * Test running a valid command with spaces in the name
     *
     * @return void
     */
    public function testRunValidCommandNestedName()
    {
        $app = $this->makeAppWithCommands([
            'tool build assets' => DemoCommand::class,
            'tool' => AbortCommand::class,
        ]);
        $output = new ConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'tool', 'build', 'assets'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('Demo Command!', $messages);
    }

    /**
     * Test using a custom factory
     *
     * @return void
     */
    public function testRunWithCustomFactory()
    {
        $output = new ConsoleOutput();
        $io = $this->getMockIo($output);
        $factory = $this->createMock(CommandFactoryInterface::class);
        $factory->expects($this->once())
            ->method('create')
            ->with(DemoCommand::class)
            ->willReturn(new DemoCommand());

        $app = $this->makeAppWithCommands(['ex' => DemoCommand::class]);

        $runner = new CommandRunner($app, 'cake', $factory);
        $result = $runner->run(['cake', 'ex'], $io);
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('Demo Command!', $messages);
    }

    /**
     * Test running a command class' help
     *
     * @return void
     */
    public function testRunValidCommandClassHelp()
    {
        $app = $this->makeAppWithCommands(['ex' => DemoCommand::class]);
        $output = new ConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'ex', '-h'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString("\ncake ex [-h]", $messages);
        $this->assertStringNotContainsString('Demo Command!', $messages);
    }

    /**
     * Test that run() fires off the buildCommands event.
     *
     * @return void
     */
    public function testRunTriggersBuildCommandsEvent()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
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

    /**
     * Test that run calls plugin hook methods
     *
     * @return void
     */
    public function testRunCallsPluginHookMethods()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods([
                'middleware', 'bootstrap', 'routes',
                'pluginBootstrap', 'pluginConsole', 'pluginRoutes',
            ])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $app->expects($this->at(0))->method('bootstrap');
        $app->expects($this->at(1))->method('pluginBootstrap');

        $commands = new CommandCollection();
        $app->expects($this->at(2))
            ->method('pluginConsole')
            ->with($this->isinstanceOf(CommandCollection::class))
            ->will($this->returnCallback(function ($commands) {
                return $commands;
            }));
        $app->expects($this->at(3))->method('routes');
        $app->expects($this->at(4))->method('pluginRoutes');

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', '--version'], $this->getMockIo($output));
        $this->assertStringContainsString(Configure::version(), $output->messages()[0]);
    }

    /**
     * Test that run() loads routing.
     *
     * @return void
     */
    public function testRunLoadsRoutes()
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap'])
            ->setConstructorArgs([TEST_APP . 'config' . DS])
            ->getMock();

        $output = new ConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $runner->run(['cake', '--version'], $this->getMockIo($output));
        $this->assertGreaterThan(2, count(Router::getRouteCollection()->routes()));
    }

    protected function makeAppWithCommands($commands)
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'console', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();
        $collection = new CommandCollection($commands);
        $app->method('console')->will($this->returnValue($collection));

        return $app;
    }

    protected function getMockIo($output)
    {
        $io = $this->getMockBuilder(ConsoleIo::class)
            ->setConstructorArgs([$output, $output, null, null])
            ->addMethods(['in'])
            ->getMock();

        return $io;
    }
}
