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
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Shell\I18nShell;
use Cake\Shell\RoutesShell;
use Cake\TestSuite\TestCase;
use stdClass;
use TestApp\Command\DemoCommand;

/**
 * Test case for the CommandCollection
 */
class CommandCollectionTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
    }

    /**
     * Test constructor with valid classnames
     *
     * @return void
     */
    public function testConstructor()
    {
        $collection = new CommandCollection([
            'i18n' => I18nShell::class,
            'routes' => RoutesShell::class
        ]);
        $this->assertTrue($collection->has('routes'));
        $this->assertTrue($collection->has('i18n'));
        $this->assertCount(2, $collection);
    }

    /**
     * Constructor with invalid class names should blow up
     *
     * @return void
     */
    public function testConstructorInvalidClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot use \'stdClass\' for command \'nope\' it is not a subclass of Cake\Console\Shell');
        new CommandCollection([
            'i18n' => I18nShell::class,
            'nope' => stdClass::class
        ]);
    }

    /**
     * Test basic add/get
     *
     * @return void
     */
    public function testAdd()
    {
        $collection = new CommandCollection();
        $this->assertSame($collection, $collection->add('routes', RoutesShell::class));
        $this->assertTrue($collection->has('routes'));
        $this->assertSame(RoutesShell::class, $collection->get('routes'));
    }

    /**
     * test adding a command instance.
     *
     * @return void
     */
    public function testAddCommand()
    {
        $collection = new CommandCollection();
        $this->assertSame($collection, $collection->add('ex', DemoCommand::class));
        $this->assertTrue($collection->has('ex'));
        $this->assertSame(DemoCommand::class, $collection->get('ex'));
    }

    /**
     * Test that add() replaces.
     *
     * @return void
     */
    public function testAddReplace()
    {
        $collection = new CommandCollection();
        $this->assertSame($collection, $collection->add('routes', RoutesShell::class));
        $this->assertSame($collection, $collection->add('routes', I18nShell::class));
        $this->assertTrue($collection->has('routes'));
        $this->assertSame(I18nShell::class, $collection->get('routes'));
    }

    /**
     * Test adding with instances
     *
     * @return void
     */
    public function testAddInstance()
    {
        $collection = new CommandCollection();
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();
        $shell = new RoutesShell($io);
        $collection->add('routes', $shell);

        $this->assertTrue($collection->has('routes'));
        $this->assertSame($shell, $collection->get('routes'));
    }

    /**
     * Instances that are not shells should fail.
     *
     */
    public function testAddInvalidInstance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot use \'stdClass\' for command \'routes\' it is not a subclass of Cake\Console\Shell');
        $collection = new CommandCollection();
        $shell = new stdClass();
        $collection->add('routes', $shell);
    }

    /**
     * Class names that are not shells should fail
     *
     */
    public function testInvalidShellClassName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot use \'stdClass\' for command \'routes\' it is not a subclass of Cake\Console\Shell');
        $collection = new CommandCollection();
        $collection->add('routes', stdClass::class);
    }

    /**
     * Test removing a command
     *
     * @return void
     */
    public function testRemove()
    {
        $collection = new CommandCollection();
        $collection->add('routes', RoutesShell::class);
        $this->assertSame($collection, $collection->remove('routes'));
        $this->assertFalse($collection->has('routes'));
    }

    /**
     * Removing an unknown command does not fail
     *
     * @return void
     */
    public function testRemoveUnknown()
    {
        $collection = new CommandCollection();
        $this->assertSame($collection, $collection->remove('nope'));
        $this->assertFalse($collection->has('nope'));
    }

    /**
     * test getIterator
     *
     * @return void
     */
    public function testGetIterator()
    {
        $in = [
            'i18n' => I18nShell::class,
            'routes' => RoutesShell::class
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
     *
     * @return void
     */
    public function testAutoDiscoverApp()
    {
        $collection = new CommandCollection();
        $collection->addMany($collection->autoDiscover());

        $this->assertTrue($collection->has('app'));
        $this->assertTrue($collection->has('demo'));
        $this->assertTrue($collection->has('i18m'));
        $this->assertTrue($collection->has('sample'));
        $this->assertTrue($collection->has('testing_dispatch'));

        $this->assertSame('TestApp\Shell\AppShell', $collection->get('app'));
        $this->assertSame('TestApp\Command\DemoCommand', $collection->get('demo'));
        $this->assertSame('TestApp\Shell\I18mShell', $collection->get('i18m'));
        $this->assertSame('TestApp\Shell\SampleShell', $collection->get('sample'));
    }

    /**
     * test autodiscovering core shells
     *
     * @return void
     */
    public function testAutoDiscoverCore()
    {
        $collection = new CommandCollection();
        $collection->addMany($collection->autoDiscover());

        $this->assertTrue($collection->has('version'));
        $this->assertTrue($collection->has('routes'));
        $this->assertTrue($collection->has('i18n'));
        $this->assertTrue($collection->has('orm_cache'));
        $this->assertTrue($collection->has('server'));
        $this->assertTrue($collection->has('cache'));
        $this->assertFalse($collection->has('command_list'), 'Hidden commands should stay hidden');

        // These have to be strings as ::class uses the local namespace.
        $this->assertSame('Cake\Shell\RoutesShell', $collection->get('routes'));
        $this->assertSame('Cake\Shell\I18nShell', $collection->get('i18n'));
        $this->assertSame('Cake\Command\VersionCommand', $collection->get('version'));
    }

    /**
     * test missing plugin discovery
     *
     * @return void
     */
    public function testDiscoverPluginUnknown()
    {
        $collection = new CommandCollection();
        $this->assertSame([], $collection->discoverPlugin('Nope'));
    }

    /**
     * test autodiscovering plugin shells
     *
     * @return void
     */
    public function testDiscoverPlugin()
    {
        Plugin::load('TestPlugin');
        Plugin::load('Company/TestPluginThree');

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
        $this->assertEquals('TestPlugin\Shell\ExampleShell', $result['example']);
        $this->assertEquals($result['example'], $result['test_plugin.example']);
        $this->assertEquals('TestPlugin\Shell\SampleShell', $result['test_plugin.sample']);

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
    }
}
