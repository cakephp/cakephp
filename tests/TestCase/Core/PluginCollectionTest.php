<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\Exception\MissingPluginException;
use Cake\Core\PluginCollection;
use Cake\Core\PluginInterface;
use Cake\TestSuite\TestCase;
use Company\TestPluginThree\Plugin as TestPluginThree;
use InvalidArgumentException;
use TestPlugin\Plugin as TestPlugin;

/**
 * PluginCollection Test
 */
class PluginCollectionTest extends TestCase
{
    public function testConstructor()
    {
        $plugins = new PluginCollection([new TestPlugin()]);

        $this->assertCount(1, $plugins);
        $this->assertTrue($plugins->has('TestPlugin'));
    }

    public function testAdd()
    {
        $plugins = new PluginCollection();
        $this->assertCount(0, $plugins);

        $plugins->add(new TestPlugin());
        $this->assertCount(1, $plugins);
    }

    public function testAddOperations()
    {
        $plugins = new PluginCollection();
        $plugins->add(new TestPlugin());

        $this->assertFalse($plugins->has('Nope'));
        $this->assertSame($plugins, $plugins->remove('Nope'));

        $this->assertTrue($plugins->has('TestPlugin'));
        $this->assertSame($plugins, $plugins->remove('TestPlugin'));
        $this->assertCount(0, $plugins);
        $this->assertFalse($plugins->has('TestPlugin'));
    }

    public function testAddVendoredPlugin()
    {
        $plugins = new PluginCollection();
        $plugins->add(new TestPluginThree());

        $this->assertTrue($plugins->has('Company/TestPluginThree'));
        $this->assertFalse($plugins->has('TestPluginThree'));
        $this->assertFalse($plugins->has('Company'));
        $this->assertFalse($plugins->has('TestPlugin'));
    }

    public function testHas()
    {
        $plugins = new PluginCollection();
        $this->assertFalse($plugins->has('TestPlugin'));

        $plugins->add(new TestPlugin());
        $this->assertTrue($plugins->has('TestPlugin'));
        $this->assertFalse($plugins->has('Plugin'));
    }

    public function testGet()
    {
        $plugins = new PluginCollection();
        $plugin = new TestPlugin();
        $plugins->add($plugin);

        $this->assertSame($plugin, $plugins->get('TestPlugin'));
    }

    public function testGetInvalid()
    {
        $this->expectException(MissingPluginException::class);

        $plugins = new PluginCollection();
        $plugins->get('Invalid');
    }

    public function testIterator()
    {
        $data = [
            new TestPlugin(),
            new TestPluginThree()
        ];
        $plugins = new PluginCollection($data);
        $out = [];
        foreach ($plugins as $key => $plugin) {
            $this->assertInstanceOf(PluginInterface::class, $plugin);
            $out[] = $plugin;
        }
        $this->assertSame($data, $out);
    }

    public function testWith()
    {
        $plugins = new PluginCollection();
        $plugin = new TestPlugin();
        $plugin->disable('routes');

        $pluginThree = new TestPluginThree();

        $plugins->add($plugin);
        $plugins->add($pluginThree);

        $out = [];
        foreach ($plugins->with('routes') as $p) {
            $out[] = $p;
        }
        $this->assertCount(1, $out);
        $this->assertSame($pluginThree, $out[0]);
    }

    public function testWithInvalidHook()
    {
        $this->expectException(InvalidArgumentException::class);

        $plugins = new PluginCollection();
        foreach ($plugins->with('bad') as $p) {
        }
    }
}
