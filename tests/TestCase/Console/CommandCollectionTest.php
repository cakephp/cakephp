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

use Cake\AttributeResolver\AttributeResolver;
use Cake\Command\CoreCommands;
use Cake\Command\RoutesCommand;
use Cake\Command\VersionCommand;
use Cake\Console\CommandCollection;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use TestApp\Command\DemoCommand;
use TestApp\Command\SampleCommand;

/**
 * Test case for the CommandCollection
 */
class CommandCollectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        $this->clearPlugins();
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
     */
    #[DataProvider('invalidNameProvider')]
    public function testAddCommandInvalidName(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The command name `{$name}` is invalid.");
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
     * Test replacing a command
     *
     * @return void
     */
    public function testReplace(): void
    {
        $oldName = 'routes';
        $newName = 'routing';
        $collection = new CommandCollection();
        $collection->add($oldName, RoutesCommand::class);
        $collection->replace($oldName, $newName, RoutesCommand::class);
        $this->assertFalse($collection->has($oldName));
        $this->assertTrue($collection->has($newName));
        $this->assertSame(RoutesCommand::class, $collection->get($newName));
    }

    /**
     * Test replacing with an instance of a command
     *
     * @return void
     */
    public function testReplaceInstance(): void
    {
        $oldName = 'routes';
        $newName = 'routing';
        $collection = new CommandCollection();
        $command = new RoutesCommand();
        $collection->add($oldName, $command);
        $collection->replace($oldName, $newName, $command);
        $this->assertFalse($collection->has($oldName));
        $this->assertTrue($collection->has($newName));
        $this->assertSame($command, $collection->get($newName));
    }

    /**
     * Test replacing a command instance with an invalid name.
     */
    public function testReplaceCommandInvalidName(): void
    {
        $oldName = 'routes';
        $newName = ' spaced';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The command name `{$newName}` is invalid.");
        $collection = new CommandCollection();
        $collection->replace($oldName, $newName, RoutesCommand::class);
    }

    /**
     * Test has
     *
     * @return void
     */
    public function testHas(): void
    {
        $collection = new CommandCollection();
        $collection->add('routes', RoutesCommand::class);
        $this->assertTrue($collection->has('routes'));
    }

    /**
     * Test get
     *
     * @return void
     */
    public function testGet(): void
    {
        $collection = new CommandCollection();
        $collection->add('routes', RoutesCommand::class);
        $this->assertSame(RoutesCommand::class, $collection->get('routes'));
    }

    /**
     * Test get invalid
     *
     * @return void
     */
    public function testGetInvalid(): void
    {
        $collection = new CommandCollection();
        $this->expectExceptionMessage('The `invalid` is not a known command name.');
        $collection->get('invalid');
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
     * Register attributed application commands through a configured resolver.
     */
    public function testAddFromAttributes(): void
    {
        AttributeResolver::setConfig('commands', [
            'basePath' => APP,
            'paths' => ['Command/*Command.php'],
            'cache' => false,
        ]);
        try {
            $collection = new CommandCollection();
            $this->assertSame($collection, $collection->addFromAttributes('commands'));
            $this->assertSame(DemoCommand::class, $collection->get('demo'));
            $this->assertSame(SampleCommand::class, $collection->get('sample'));
            $this->assertFalse($collection->has('abstract'));
        } finally {
            AttributeResolver::drop('commands');
        }
    }

    /**
     * Core commands are available without configuring attribute discovery.
     */
    public function testCoreCommands(): void
    {
        $collection = CoreCommands::addTo(new CommandCollection());
        $this->assertSame(VersionCommand::class, $collection->get('version'));
        $this->assertSame(RoutesCommand::class, $collection->get('routes'));
        $this->assertTrue($collection->has('schema_cache build'));
        $this->assertTrue($collection->has('attributes warm'));
        $this->assertFalse($collection->has('sample'));
        $this->assertFalse($collection->has('command_list'));
    }

    /**
     * Core registration preserves explicit application overrides.
     */
    public function testCoreCommandsPreserveExplicitRegistration(): void
    {
        $collection = new CommandCollection(['version' => SampleCommand::class]);
        CoreCommands::addTo($collection);
        $this->assertSame(SampleCommand::class, $collection->get('version'));
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
