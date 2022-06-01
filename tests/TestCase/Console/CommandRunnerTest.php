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
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Core\Configure;
use Cake\Core\ConsoleApplicationInterface;
use Cake\Event\EventManager;
use Cake\Http\BaseApplication;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use RuntimeException;
use stdClass;
use TestApp\Command\AbortCommand;
use TestApp\Command\DemoCommand;
use TestApp\Command\DependencyCommand;
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
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        $this->config = dirname(dirname(__DIR__));
    }

    /**
     * test event manager proxies to the application.
     */
    public function testEventManagerProxies(): void
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
     */
    public function testGetEventManagerNonEventedApplication(): void
    {
        $app = $this->createMock(ConsoleApplicationInterface::class);

        $runner = new CommandRunner($app);
        $this->assertSame(EventManager::instance(), $runner->getEventManager());
    }

    /**
     * test event manager cannot be set on applications without events.
     */
    public function testSetEventManagerNonEventedApplication(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $app = $this->createMock(ConsoleApplicationInterface::class);

        $events = new EventManager();
        $runner = new CommandRunner($app);
        $runner->setEventManager($events);
    }

    /**
     * Test that running with empty argv fails
     */
    public function testRunMissingRootCommand(): void
    {
        $this->expectException(RuntimeException::class);
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
     */
    public function testRunInvalidCommand(): void
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new StubConsoleOutput();
        $runner = new CommandRunner($app);
        $runner->run(['cake', 'nope', 'nope', 'nope'], $this->getMockIo($output));

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString(
            'Unknown command `cake nope`. Run `cake --help` to get the list of commands.',
            $messages
        );
    }

    /**
     * Test that using special characters in an unknown command does
     * not cause a PHP error.
     */
    public function testRunInvalidCommandWithSpecialCharacters(): void
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new StubConsoleOutput();
        $runner = new CommandRunner($app);
        $runner->run(['cake', 's/pec[ial'], $this->getMockIo($output));

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString(
            'Unknown command `cake s/pec[ial`. Run `cake --help` to get the list of commands.',
            $messages
        );
    }

    /**
     * Test that running an unknown command gives suggestions.
     */
    public function testRunInvalidCommandSuggestion(): void
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new StubConsoleOutput();
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
     */
    public function testRunHelpLongOption(): void
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new StubConsoleOutput();
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
     */
    public function testRunHelpShortOption(): void
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new StubConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', '-h'], $this->getMockIo($output));
        $this->assertSame(0, $result);
        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('- i18n', $messages);
        $this->assertStringContainsString('Available Commands', $messages);
    }

    /**
     * Test that no command outputs the command list
     */
    public function testRunNoCommand(): void
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new StubConsoleOutput();
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
     */
    public function testRunVersionAlias(): void
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new StubConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', '--version'], $this->getMockIo($output));
        $this->assertStringContainsString(Configure::version(), $output->messages()[0]);
    }

    /**
     * Test running a valid command
     */
    public function testRunValidCommand(): void
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new StubConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'routes'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $contents = implode("\n", $output->messages());
        $this->assertStringContainsString('URI template', $contents);
    }

    /**
     * Test running a valid command and that backwards compatible
     * inflection is hooked up.
     */
    public function testRunValidCommandInflection(): void
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new StubConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'schema_cache', 'build'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $contents = implode("\n", $output->messages());
        $this->assertStringContainsString('Cache', $contents);
    }

    /**
     * Test running a valid raising an error
     */
    public function testRunValidCommandWithAbort(): void
    {
        $app = $this->makeAppWithCommands(['failure' => SampleShell::class]);
        $output = new StubConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'failure', 'with_abort'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_ERROR, $result);
    }

    /**
     * Test returning a non-zero value
     */
    public function testRunValidCommandReturnInteger(): void
    {
        $app = $this->makeAppWithCommands(['failure' => SampleShell::class]);
        $output = new StubConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'failure', 'returnValue'], $this->getMockIo($output));
        $this->assertSame(99, $result);
    }

    /**
     * Ensure that the root command name propagates to shell help
     */
    public function testRunRootNamePropagates(): void
    {
        $app = $this->makeAppWithCommands(['sample' => SampleShell::class]);
        $output = new StubConsoleOutput();

        $runner = new CommandRunner($app, 'widget');
        $runner->run(['widget', 'sample', '-h'], $this->getMockIo($output));
        $result = implode("\n", $output->messages());
        $this->assertStringContainsString('widget sample [-h]', $result);
        $this->assertStringNotContainsString('cake sample [-h]', $result);
    }

    /**
     * Test running a valid command
     */
    public function testRunValidCommandClass(): void
    {
        $app = $this->makeAppWithCommands(['ex' => DemoCommand::class]);
        $output = new StubConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'ex'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('Demo Command!', $messages);
    }

    /**
     * Test running a valid command with spaces in the name
     */
    public function testRunValidCommandSubcommandName(): void
    {
        $app = $this->makeAppWithCommands([
            'tool build' => DemoCommand::class,
            'tool' => AbortCommand::class,
        ]);
        $output = new StubConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'tool', 'build'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('Demo Command!', $messages);
    }

    /**
     * Test running a valid command with spaces in the name
     */
    public function testRunValidCommandNestedName(): void
    {
        $app = $this->makeAppWithCommands([
            'tool build assets' => DemoCommand::class,
            'tool' => AbortCommand::class,
        ]);
        $output = new StubConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'tool', 'build', 'assets'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('Demo Command!', $messages);
    }

    /**
     * Test using a custom factory
     */
    public function testRunWithCustomFactory(): void
    {
        $output = new StubConsoleOutput();
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

    public function testRunWithContainerDependencies(): void
    {
        $app = $this->makeAppWithCommands([
            'dependency' => DependencyCommand::class,
        ]);
        $container = $app->getContainer();
        $container->add(stdClass::class, json_decode('{"key":"value"}'));
        $container->add(DependencyCommand::class)
            ->addArgument(stdClass::class);

        $output = new StubConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'dependency'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('Dependency Command', $messages);
        $this->assertStringContainsString('constructor inject: {"key":"value"}', $messages);
    }

    /**
     * Test running a command class' help
     */
    public function testRunValidCommandClassHelp(): void
    {
        $app = $this->makeAppWithCommands(['ex' => DemoCommand::class]);
        $output = new StubConsoleOutput();

        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', 'ex', '-h'], $this->getMockIo($output));
        $this->assertSame(Shell::CODE_SUCCESS, $result);

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString("\ncake ex [-h]", $messages);
        $this->assertStringNotContainsString('Demo Command!', $messages);
    }

    /**
     * Test that run() fires off the buildCommands event.
     */
    public function testRunTriggersBuildCommandsEvent(): void
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $output = new StubConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $runner->getEventManager()->on('Console.buildCommands', function ($event, $commands): void {
            $this->assertInstanceOf(CommandCollection::class, $commands);
            $this->eventTriggered = true;
        });
        $result = $runner->run(['cake', '--version'], $this->getMockIo($output));
        $this->assertTrue($this->eventTriggered, 'Should have triggered event.');
    }

    /**
     * Test that run calls plugin hook methods
     */
    public function testRunCallsPluginHookMethods(): void
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods([
                'middleware', 'bootstrap', 'routes',
                'pluginBootstrap', 'pluginConsole', 'pluginRoutes',
            ])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $app->expects($this->once())->method('bootstrap');
        $app->expects($this->once())->method('pluginBootstrap');

        $commands = new CommandCollection();
        $app->expects($this->once())
            ->method('pluginConsole')
            ->with($this->isinstanceOf(CommandCollection::class))
            ->will($this->returnCallback(function ($commands) {
                return $commands;
            }));
        $app->expects($this->once())->method('routes');
        $app->expects($this->once())->method('pluginRoutes');

        $output = new StubConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $result = $runner->run(['cake', '--version'], $this->getMockIo($output));
        $this->assertStringContainsString(Configure::version(), $output->messages()[0]);
    }

    /**
     * Test that run() loads routing.
     */
    public function testRunLoadsRoutes(): void
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap'])
            ->setConstructorArgs([TEST_APP . 'config' . DS])
            ->getMock();

        $output = new StubConsoleOutput();
        $runner = new CommandRunner($app, 'cake');
        $runner->run(['cake', '--version'], $this->getMockIo($output));
        $this->assertGreaterThan(2, count(Router::getRouteCollection()->routes()));
    }

    protected function makeAppWithCommands(array $commands): BaseApplication
    {
        $app = $this->getMockBuilder(BaseApplication::class)
            ->onlyMethods(['middleware', 'bootstrap', 'console', 'routes'])
            ->setConstructorArgs([$this->config])
            ->getMock();
        $collection = new CommandCollection($commands);
        $app->method('console')->will($this->returnValue($collection));

        return $app;
    }

    protected function getMockIo(StubConsoleOutput $output): ConsoleIo
    {
        $io = $this->getMockBuilder(ConsoleIo::class)
            ->setConstructorArgs([$output, $output, null, null])
            ->addMethods(['in'])
            ->getMock();

        return $io;
    }
}
