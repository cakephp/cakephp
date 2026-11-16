<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\AttributeResolver\AttributeCache;
use Cake\AttributeResolver\AttributeCollection;
use Cake\AttributeResolver\AttributeResolver;
use Cake\AttributeResolver\ValueObject\AttributeInfo;
use Cake\Cache\Cache;
use Cake\Command\Command;
use Cake\Command\CompletionCommand;
use Cake\Console\Attribute\AsCommand;
use Cake\Console\AttributeCommandConnector;
use Cake\Console\CommandCollection;
use Cake\Console\CommandFactory;
use Cake\Console\CommandRunner;
use Cake\Console\ConsoleIo;
use Cake\Console\Exception\CommandAttributeException;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Container\Container;
use Cake\Core\ConsoleApplicationInterface;
use Cake\TestSuite\TestCase;
use Company\TestPluginThree\Command\CompanyCommand;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use TestApp\Command\SampleCommand as AppSampleCommand;
use TestApp\Console\Attribute\Nested\Export;
use TestPlugin\Command\ExampleCommand;
use TestPlugin\Command\SampleCommand;

/**
 * Verify resolver-based registration, plugin precedence, and lazy discovery.
 */
class AttributeCommandConnectorTest extends TestCase
{
    /**
     * Configure command fixtures independently of application discovery.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearPlugins();
        AttributeResolver::setConfig('command_connector', [
            'basePath' => APP,
            'paths' => ['Console/Attribute/*.php', 'Console/Attribute/Nested/*.php', 'src/Command/*.php'],
            'cache' => false,
        ]);
    }

    /**
     * Clear configured metadata and loaded plugins between cases.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        AttributeResolver::drop('command_connector');
        AttributeResolver::drop('command_errors');
        Cache::clear('_cake_attributes_');
        $this->clearPlugins();
        parent::tearDown();
    }

    /**
     * Nested classes need neither a Command suffix nor constructor invocation.
     */
    public function testDiscoversAnnotatedClassesOnly(): void
    {
        $commands = new CommandCollection();
        new AttributeCommandConnector($commands)->connect('command_connector');

        $this->assertSame(['attribute export' => Export::class], iterator_to_array($commands));
    }

    /**
     * Plugin collisions preserve existing short names and expose qualified names.
     */
    public function testPluginQualificationAndCollisions(): void
    {
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);
        $commands = new CommandCollection(['sample' => Command::class]);
        $commands->addFromAttributes('command_connector');

        $this->assertSame(Command::class, $commands->get('sample'));
        $this->assertSame(SampleCommand::class, $commands->get('test_plugin.sample'));
        $this->assertSame(ExampleCommand::class, $commands->get('example'));
        $this->assertSame(ExampleCommand::class, $commands->get('test_plugin.example'));
        $this->assertSame(CompanyCommand::class, $commands->get('company/test_plugin_three.company'));
    }

    /**
     * Resolver scans must not enable a plugin's disabled console feature.
     */
    public function testSkipsConsoleDisabledPlugin(): void
    {
        $this->loadPlugins(['TestPlugin' => ['console' => false]]);
        $commands = new CommandCollection();
        $commands->addFromAttributes('command_connector');

        $this->assertSame(['attribute export'], $commands->keys());
    }

    /**
     * Cached metadata for a plugin cannot register it after it is unloaded.
     */
    public function testSkipsUnloadedPluginFromCachedCollection(): void
    {
        $this->loadPlugins(['TestPlugin']);
        AttributeResolver::collection('command_connector');
        $this->clearPlugins();
        $commands = new CommandCollection();
        $commands->addFromAttributes('command_connector');

        $this->assertSame(['attribute export'], $commands->keys());
    }

    /**
     * Application commands take priority independently of metadata iteration order.
     */
    public function testApplicationPrecedesPlugin(): void
    {
        $this->loadPlugins(['TestPlugin']);
        AttributeResolver::drop('command_connector');
        AttributeResolver::setConfig('command_connector', [
            'basePath' => APP,
            'paths' => ['Command/SampleCommand.php', 'src/Command/SampleCommand.php'],
            'cache' => false,
        ]);
        $commands = new CommandCollection();
        $commands->addFromAttributes('command_connector');

        $this->assertSame(AppSampleCommand::class, $commands->get('sample'));
        $this->assertSame(SampleCommand::class, $commands->get('test_plugin.sample'));
    }

    /**
     * Persisted resolver data supports repeated registration without new scans.
     */
    public function testCachedDiscoveryAndExclusions(): void
    {
        AttributeResolver::drop('command_connector');
        AttributeResolver::setConfig('command_connector', [
            'basePath' => APP,
            'paths' => ['Console/Attribute/**/*.php'],
            'excludePaths' => ['Invalid'],
            'cache' => '_cake_attributes_',
        ]);
        $first = new CommandCollection()->addFromAttributes('command_connector');
        AttributeResolver::drop('command_connector');
        AttributeResolver::setConfig('command_connector', [
            'paths' => [],
            'cache' => '_cake_attributes_',
        ]);
        $second = new CommandCollection()->addFromAttributes('command_connector');

        $this->assertSame(['attribute export'], $first->keys());
        $this->assertSame(iterator_to_array($first), iterator_to_array($second));
    }

    /**
     * Discovery requires an existing resolver configuration like routes and events.
     */
    public function testMissingConfiguration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CommandCollection()->addFromAttributes('missing_commands');
    }

    /**
     * Invalid declarations fail with the declaring class in the diagnostic.
     *
     * @param string $class Fixture class basename.
     */
    #[DataProvider('invalidDeclarationProvider')]
    public function testInvalidDeclaration(string $class): void
    {
        AttributeResolver::setConfig('command_errors', [
            'basePath' => APP,
            'paths' => ['Console/Attribute/Invalid/' . $class . '.php'],
            'cache' => false,
        ]);
        $this->expectException(CommandAttributeException::class);
        $this->expectExceptionMessage($class);
        new CommandCollection()->addFromAttributes('command_errors');
    }

    /**
     * Supply invalid command identities and attribute targets.
     *
     * @return array<string, array{string}>
     */
    public static function invalidDeclarationProvider(): array
    {
        return [
            'not a command' => ['Service'],
            'repeated identity' => ['RepeatedCommand'],
            'method target' => ['MethodCommand'],
        ];
    }

    /**
     * Discovered commands work with help, completion, and constructor injection.
     */
    public function testRunnerHelpCompletionAndDependencyInjection(): void
    {
        $app = new class implements ConsoleApplicationInterface {
            /**
             * Resolver configuration is provided by the test setup.
             *
             * @return void
             */
            public function bootstrap(): void
            {
            }

            /**
             * Explicitly register commands from the shared resolver.
             *
             * @param \Cake\Console\CommandCollection $commands Command registry.
             * @return \Cake\Console\CommandCollection
             */
            public function console(CommandCollection $commands): CommandCollection
            {
                return $commands->add('completion', CompletionCommand::class)
                    ->addFromAttributes('command_connector');
            }
        };
        $container = new Container();
        $container->add(stdClass::class, new stdClass());
        $runner = new CommandRunner($app, 'cake', new CommandFactory($container));

        $output = new StubConsoleOutput();
        $this->assertSame(0, $runner->run(['cake', '--help'], new ConsoleIo($output, $output)));
        $this->assertStringContainsString('attribute export', $output->output());
        $this->assertStringContainsString('Export attributed data.', $output->output());

        $output = new StubConsoleOutput();
        $this->assertSame(0, $runner->run(['cake', '--help', '-v'], new ConsoleIo($output, $output)));
        $this->assertStringContainsString('attribute export', $output->output());

        $output = new StubConsoleOutput();
        $this->assertSame(0, $runner->run(['cake', 'completion', 'options', 'attribute', 'export'], new ConsoleIo($output, $output)));
        $this->assertStringContainsString('--format', $output->output());

        $output = new StubConsoleOutput();
        $this->assertSame(0, $runner->run(['cake', 'attribute', 'export', 'result', '--format', 'json'], new ConsoleIo($output, $output)));
        $this->assertSame('result:json', $output->output());
    }

    /**
     * Excluding the discovery attribute prevents registration from that collection.
     */
    public function testExcludedDiscoveryAttribute(): void
    {
        AttributeResolver::drop('command_connector');
        AttributeResolver::setConfig('command_connector', [
            'basePath' => APP,
            'paths' => ['Console/Attribute/Nested/*.php'],
            'excludeAttributes' => [AsCommand::class],
            'cache' => false,
        ]);
        $commands = new CommandCollection()->addFromAttributes('command_connector');
        $this->assertCount(0, $commands);
    }

    /**
     * Discovery uses persisted names until the configured metadata cache is refreshed.
     */
    public function testRegistrationUsesCachedMetadata(): void
    {
        $info = AttributeResolver::forClass(Export::class)->withAttribute(AsCommand::class)->first();
        $cached = AttributeInfo::fromArray(['arguments' => ['name' => 'cached export']] + $info->toArray());
        new AttributeCache('_cake_attributes_')->write('command_connector', new AttributeCollection([$cached]));
        AttributeResolver::drop('command_connector');
        AttributeResolver::setConfig('command_connector', [
            'paths' => [],
            'cache' => '_cake_attributes_',
        ]);
        $commands = new CommandCollection()->addFromAttributes('command_connector');

        $this->assertSame(['cached export' => Export::class], iterator_to_array($commands));
    }
}
