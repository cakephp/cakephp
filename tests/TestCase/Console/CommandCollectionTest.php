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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Command\RoutesCommand;
use Cake\Command\VersionCommand;
use Cake\Console\CommandCollection;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestApp\Command\DemoCommand;
use TestApp\Command\SampleCommand;

/**
 * Test case for the CommandCollection
 */
class CommandCollectionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
    }

    /**
     * Test constructor with valid classnames
     */
    public function testConstructor(): void
    {
        $collection = new CommandCollection([
            'sample' => SampleCommand::class,
            'routes' => RoutesCommand::class,
        ]);
        $this->assertTrue($collection->has('routes'));
        $this->assertTrue($collection->has('sample'));
        $this->assertCount(2, $collection);
    }

    /**
     * Test basic add/get
     */
    public function testAdd(): void
    {
        $collection = new CommandCollection();
        $this->assertSame($collection, $collection->add('routes', RoutesCommand::class));
        $this->assertTrue($collection->has('routes'));
        $this->assertSame(RoutesCommand::class, $collection->get('routes'));
    }

    /**
     * test adding a command instance.
     */
    public function testAddCommand(): void
    {
        $collection = new CommandCollection();
        $this->assertSame($collection, $collection->add('ex', DemoCommand::class));
        $this->assertTrue($collection->has('ex'));
        $this->assertSame(DemoCommand::class, $collection->get('ex'));
    }

    /**
     * Test that add() replaces.
     */
    public function testAddReplace(): void
    {
        $collection = new CommandCollection();
        $this->assertSame($collection, $collection->add('routes', RoutesCommand::class));
        $this->assertSame($collection, $collection->add('routes', SampleCommand::class));
        $this->assertTrue($collection->has('routes'));
        $this->assertSame(SampleCommand::class, $collection->get('routes'));
    }

    /**
     * Test adding with instances
     */
    public function testAddInstance(): void
    {
        $collection = new CommandCollection();
        $command = new RoutesCommand();
        $collection->add('routes', $command);

        $this->assertTrue($collection->has('routes'));
        $this->assertSame($command, $collection->get('routes'));
    }

    /**
     * Provider for invalid names.
     *
     * @return array
     */
    public static function invalidNameProvider(): array
    {
        return [
            // Empty
            [''],
            // Leading spaces
            [' spaced'],
            // Trailing spaces
            ['spaced '],
            // Too many words
            ['one two three four'],
        ];
    }

    /**
     * test adding a command instance.
     *
     * @dataProvider invalidNameProvider
     */
    public function testAddCommandInvalidName(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The command name `$name` is invalid.");
        $collection = new CommandCollection();
        $collection->add($name, DemoCommand::class);
    }

    /**
     * Test removing a command
     */
    public function testRemove(): void
    {
        $collection = new CommandCollection();
        $collection->add('routes', RoutesCommand::class);
        $this->assertSame($collection, $collection->remove('routes'));
        $this->assertFalse($collection->has('routes'));
    }

    /**
     * Removing an unknown command does not fail
     */
    public function testRemoveUnknown(): void
    {
        $collection = new CommandCollection();
        $this->assertSame($collection, $collection->remove('nope'));
        $this->assertFalse($collection->has('nope'));
    }

    /**
     * test getIterator
     */
    public function testGetIterator(): void
    {
        $in = [
            'sample' => SampleCommand::class,
            'routes' => RoutesCommand::class,
        ];
        $collection = new CommandCollection($in);
        $out = [];
        foreach ($collection as $key => $value) {
            $out[$key] = $value;
        }
        $this->assertEquals($in, $out);
    }

    /**
     * test autodiscovering app shells
     */
    public function testAutoDiscoverApp(): void
    {
        $collection = new CommandCollection();
        $collection->addMany($collection->autoDiscover());

        $this->assertTrue($collection->has('demo'));
        $this->assertTrue($collection->has('sample'));

        $this->assertSame('TestApp\Command\DemoCommand', $collection->get('demo'));
        $this->assertSame('TestApp\Command\SampleCommand', $collection->get('sample'));
    }

    /**
     * test autodiscovering core shells
     */
    public function testAutoDiscoverCore(): void
    {
        $collection = new CommandCollection();
        $collection->addMany($collection->autoDiscover());

        $this->assertTrue($collection->has('version'));
        $this->assertTrue($collection->has('routes'));
        $this->assertTrue($collection->has('sample'));
        $this->assertTrue($collection->has('schema_cache build'));
        $this->assertTrue($collection->has('schema_cache clear'));
        $this->assertTrue($collection->has('server'));
        $this->assertTrue($collection->has('cache clear'));
        $this->assertFalse($collection->has('command_list'), 'Hidden commands should stay hidden');

        // These have to be strings as ::class uses the local namespace.
        $this->assertSame(RoutesCommand::class, $collection->get('routes'));
        $this->assertSame(SampleCommand::class, $collection->get('sample'));
        $this->assertSame(VersionCommand::class, $collection->get('version'));
    }

    /**
     * test missing plugin discovery
     */
    public function testDiscoverPluginUnknown(): void
    {
        $collection = new CommandCollection();
        $this->assertSame([], $collection->discoverPlugin('Nope'));
    }

    /**
     * test autodiscovering plugin shells
     */
    public function testDiscoverPlugin(): void
    {
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);

        $collection = new CommandCollection();
        // Add a dupe to test de-duping
        $collection->add('sample', DemoCommand::class);

        $result = $collection->discoverPlugin('TestPlugin');

        $this->assertArrayHasKey(
            'example',
            $result,
            'Used short name for unique plugin shell'
        );
        $this->assertArrayHasKey(
            'test_plugin.example',
            $result,
            'Long names are stored for unique shells'
        );
        $this->assertArrayNotHasKey('sample', $result, 'Existing command not output');
        $this->assertArrayHasKey(
            'test_plugin.sample',
            $result,
            'Duplicate shell was given a full alias'
        );
        $this->assertSame('TestPlugin\Command\ExampleCommand', $result['example']);
        $this->assertSame($result['example'], $result['test_plugin.example']);
        $this->assertSame('TestPlugin\Command\SampleCommand', $result['test_plugin.sample']);

        $result = $collection->discoverPlugin('Company/TestPluginThree');
        $this->assertArrayHasKey(
            'company',
            $result,
            'Used short name for unique plugin shell'
        );
        $this->assertArrayHasKey(
            'company/test_plugin_three.company',
            $result,
            'Long names are stored as well'
        );
        $this->assertSame($result['company'], $result['company/test_plugin_three.company']);
        $this->clearPlugins();
    }

    /**
     * Test keys
     */
    public function testKeys(): void
    {
        $collection = new CommandCollection();
        $collection->add('demo', DemoCommand::class);
        $collection->add('demo sample', DemoCommand::class);
        $collection->add('dang', DemoCommand::class);

        $result = $collection->keys();
        $this->assertSame(['demo', 'demo sample', 'dang'], $result);
    }
}
